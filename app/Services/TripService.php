<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CommissionState;
use App\Enums\DriverStatus;
use App\Enums\TripPaymentStatus;
use App\Enums\TripStatus;
use App\Exceptions\DomainException;
use App\Jobs\FindDriversForTripJob;
use App\Jobs\SyncTripToFirebaseJob;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Trips\Models\Trip;
use App\Modules\Trips\Models\TripStatusLog;
use App\Modules\Users\Models\User;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Support\Facades\DB;

final class TripService
{
    public function __construct(
        private readonly TripStateValidator $stateValidator,
        private readonly CommissionCalculator $commissionCalculator,
        private readonly WalletService $walletService,
        private readonly DriverPresenceService $presenceService,
    ) {}

    public function createTrip(User $customer, array $payload): Trip
    {
        if (! $customer->isCustomer()) {
            throw new DomainException('Only customers can create trips.');
        }

        $trip = Trip::query()->create([
            'customer_id' => $customer->id,
            'vehicle_type_id' => $payload['vehicle_type_id'],
            'pickup_location' => $payload['pickup_location'],
            'destination_location' => $payload['destination_location'],
            'cargo_description' => $payload['cargo_description'] ?? null,
            'cargo_photo' => $payload['cargo_photo'] ?? null,
            'estimated_price_minor' => (int) $payload['estimated_price_minor'],
            'trip_status' => TripStatus::Requested,
            'payment_status' => TripPaymentStatus::Unpaid,
            'commission_state' => CommissionState::None,
        ]);

        $this->logStatusChange($trip, null, TripStatus::Requested, $customer->id);

        FindDriversForTripJob::dispatch($trip->id)->onQueue('matching');

        return $trip;
    }

    public function acceptTrip(User $driverUser, Trip $trip, string $vehicleId): Trip
    {
        if (! $driverUser->isDriver()) {
            throw new DomainException('Only drivers can accept trips.');
        }

        $driver = $driverUser->driver;
        if (! $driver instanceof Driver) {
            throw new DomainException('Driver profile is missing.');
        }

        if ($driver->status !== DriverStatus::Approved) {
            throw new DomainException('Driver is not approved for operations.');
        }

        if ($trip->trip_status !== TripStatus::Requested) {
            throw new DomainException('Trip is not open for acceptance.');
        }

        $vehicle = Vehicle::query()->whereKey($vehicleId)->where('driver_id', $driver->id)->first();
        if (! $vehicle) {
            throw new DomainException('Vehicle not assigned to this driver.');
        }

        if ($vehicle->vehicle_type_id !== $trip->vehicle_type_id) {
            throw new DomainException('Vehicle type does not match trip requirement.');
        }

        $wallet = $this->walletService->getOrCreateWallet($driverUser);
        $commission = $this->commissionCalculator->estimatedAmountMinor($trip);
        $this->walletService->assertAvailableMinor($wallet, $commission);

        return DB::transaction(function () use ($trip, $driverUser, $vehicle, $driver): Trip {
            $trip->driver_id = $driverUser->id;
            $trip->vehicle_id = $vehicle->id;
            $trip->trip_status = TripStatus::Accepted;
            $trip->accepted_at = now();
            $trip->save();

            $this->logStatusChange($trip, TripStatus::Requested, TripStatus::Accepted, $driverUser->id);

            $this->presenceService->markBusy($driver);

            SyncTripToFirebaseJob::dispatch($trip->id)->onQueue('firebase');

            return $trip->fresh();
        });
    }

    public function advanceStatus(Trip $trip, TripStatus $to, User $actor): Trip
    {
        $from = $trip->trip_status;
        $this->stateValidator->assertCanTransition($from, $to);

        return DB::transaction(function () use ($trip, $from, $to, $actor): Trip {
            if ($this->commissionCalculator->shouldReserveCommission($to) && $trip->commission_state === CommissionState::None) {
                $driver = $trip->driver;
                if (! $driver) {
                    throw new DomainException('Trip has no driver assigned.');
                }
                $wallet = $this->walletService->getOrCreateWallet($driver);
                $amount = $this->commissionCalculator->estimatedAmountMinor($trip);
                $this->walletService->reserveCommissionMinor($wallet, $amount, $trip, request()->ip());
                $trip->commission_amount_minor = $amount;
                $trip->commission_state = CommissionState::Reserved;
                $trip->started_at = now();
            }

            if ($to === TripStatus::Delivered) {
                $this->finalizeTripSuccess($trip);
                $this->releaseDriverPresence($trip);
            }

            $trip->trip_status = $to;
            $trip->save();

            $this->logStatusChange($trip, $from, $to, $actor->id);

            SyncTripToFirebaseJob::dispatch($trip->id)->onQueue('firebase');

            return $trip->fresh();
        });
    }

    public function cancelTrip(Trip $trip, User $actor, ?string $reason = null): Trip
    {
        $from = $trip->trip_status;
        if ($from === TripStatus::Delivered || $from === TripStatus::Cancelled) {
            throw new DomainException('Trip cannot be cancelled in the current state.');
        }

        $this->stateValidator->assertCanTransition($from, TripStatus::Cancelled);

        return DB::transaction(function () use ($trip, $from, $actor, $reason): Trip {
            if ($trip->commission_state === CommissionState::Reserved && $trip->driver_id) {
                $wallet = $this->walletService->getOrCreateWallet($trip->driver);
                if ($trip->tripStarted()) {
                    $partial = $this->commissionCalculator->amountAfterCancellationMinor($trip);
                    $this->walletService->finalizeCommissionMinor($wallet, $trip, $partial, request()->ip());
                    $trip->commission_state = CommissionState::Finalized;
                } else {
                    $this->walletService->releaseCommissionReservationMinor($wallet, $trip, request()->ip());
                    $trip->commission_state = CommissionState::Released;
                }
            }

            $trip->trip_status = TripStatus::Cancelled;
            $trip->cancellation_reason = $reason;
            $trip->cancelled_at = now();
            $trip->save();

            $this->logStatusChange($trip, $from, TripStatus::Cancelled, $actor->id, ['reason' => $reason]);

            $this->releaseDriverPresence($trip);

            SyncTripToFirebaseJob::dispatch($trip->id)->onQueue('firebase');

            return $trip->fresh();
        });
    }

    private function finalizeTripSuccess(Trip $trip): void
    {
        if ($trip->commission_state === CommissionState::Reserved && $trip->driver_id) {
            $wallet = $this->walletService->getOrCreateWallet($trip->driver);
            $this->walletService->finalizeCommissionMinor(
                $wallet,
                $trip,
                $trip->commission_amount_minor,
                request()->ip()
            );
            $trip->commission_state = CommissionState::Finalized;
            $trip->final_price_minor = $trip->estimated_price_minor;
            $trip->payment_status = TripPaymentStatus::Paid;
            $trip->completed_at = now();
            $trip->save();
        }
    }

    private function releaseDriverPresence(Trip $trip): void
    {
        $driverUser = $trip->driver;
        if (! $driverUser) {
            return;
        }

        $driver = $driverUser->driver;
        if ($driver instanceof Driver) {
            $this->presenceService->releaseAfterTrip($driver);
        }
    }

    private function logStatusChange(Trip $trip, ?TripStatus $from, TripStatus $to, ?string $actorId, array $meta = []): void
    {
        TripStatusLog::query()->create([
            'trip_id' => $trip->id,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'actor_user_id' => $actorId,
            'meta' => $meta ?: null,
            'created_at' => now(),
        ]);
    }
}
