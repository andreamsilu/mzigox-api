<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CommissionState;
use App\Enums\DriverStatus;
use App\Enums\TripPaymentStatus;
use App\Enums\TripStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VehicleStatus;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Trips\Models\Trip;
use App\Modules\Users\Models\User;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleType;
use App\Modules\Wallets\Models\Wallet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_mysql')]
class TripWalletFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_commission_reserved_on_trip_start_and_finalized_on_delivery(): void
    {
        $type = VehicleType::query()->create([
            'slug' => 'boda',
            'name' => 'Boda',
            'description' => null,
            'default_capacity_kg' => 50,
            'is_active' => true,
        ]);

        $customer = User::factory()->create([
            'role' => UserRole::Customer,
            'status' => UserStatus::Active,
            'phone' => '+255711111111',
        ]);

        $driverUser = User::factory()->driver()->create([
            'status' => UserStatus::Active,
            'phone' => '+255722222222',
        ]);

        $driver = Driver::query()->create([
            'user_id' => $driverUser->id,
            'status' => DriverStatus::Approved,
            'is_online' => true,
            'last_latitude' => -6.7924,
            'last_longitude' => 39.2083,
            'last_location_at' => now(),
        ]);

        $vehicle = Vehicle::query()->create([
            'vehicle_type_id' => $type->id,
            'owner_id' => $driverUser->id,
            'driver_id' => $driver->id,
            'plate_number' => 'T123ABC',
            'capacity_kg' => 100,
            'status' => VehicleStatus::Active,
        ]);

        $wallet = Wallet::query()->create([
            'user_id' => $driverUser->id,
            'balance_minor' => 1_000_000,
            'reserved_balance_minor' => 0,
            'currency' => 'TZS',
        ]);

        $trip = Trip::query()->create([
            'customer_id' => $customer->id,
            'vehicle_type_id' => $type->id,
            'pickup_location' => ['lat' => -6.8, 'lng' => 39.28],
            'destination_location' => ['lat' => -6.82, 'lng' => 39.30],
            'estimated_price_minor' => 100_000,
            'trip_status' => TripStatus::Requested,
            'payment_status' => TripPaymentStatus::Unpaid,
            'commission_state' => CommissionState::None,
        ]);

        $trip->driver_id = $driverUser->id;
        $trip->vehicle_id = $vehicle->id;
        $trip->trip_status = TripStatus::CargoLoaded;
        $trip->save();

        Sanctum::actingAs($driverUser);

        $this->patchJson("/api/v1/trips/{$trip->id}/status", [
            'status' => TripStatus::TripStarted->value,
        ])->assertOk();

        $wallet->refresh();
        $this->assertGreaterThan(0, $wallet->reserved_balance_minor);

        $this->patchJson("/api/v1/trips/{$trip->id}/status", [
            'status' => TripStatus::InTransit->value,
        ])->assertOk();

        $this->patchJson("/api/v1/trips/{$trip->id}/status", [
            'status' => TripStatus::Delivered->value,
        ])->assertOk();

        $wallet->refresh();
        $this->assertSame(0, $wallet->reserved_balance_minor);
        $trip->refresh();
        $this->assertSame(TripStatus::Delivered, $trip->trip_status);
    }
}
