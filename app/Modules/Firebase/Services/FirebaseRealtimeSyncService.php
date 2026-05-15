<?php

declare(strict_types=1);

namespace App\Modules\Firebase\Services;

use App\Enums\DriverPresence;
use App\Enums\TripStatus;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Trips\Models\Trip;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Database;
use Throwable;

/**
 * Laravel → Firebase bridge for authoritative trip status only.
 *
 * Driver GPS and high-frequency location updates are written by the driver
 * mobile app directly to RTDB (see FIREBASE.md). This service never stores
 * financial or permanent business data in Firebase.
 */
final class FirebaseRealtimeSyncService
{
    private function database(): ?Database
    {
        if (! app()->bound(Database::class)) {
            return null;
        }

        try {
            return app(Database::class);
        } catch (Throwable) {
            return null;
        }
    }

    public function syncTrip(Trip $trip): void
    {
        $db = $this->database();
        if (! $db) {
            return;
        }

        try {
            $trip->loadMissing(['driver.driver', 'driver']);

            $payload = [
                'status' => $trip->trip_status->value,
                'progress' => $this->progressLabel($trip->trip_status),
                'driver_location' => null,
                'eta' => null,
                'updated_at' => now()->toIso8601String(),
            ];

            $driverProfile = $trip->driver?->driver;
            if ($driverProfile instanceof Driver) {
                $payload['driver_location'] = [
                    'lat' => $driverProfile->last_latitude !== null ? (float) $driverProfile->last_latitude : null,
                    'lng' => $driverProfile->last_longitude !== null ? (float) $driverProfile->last_longitude : null,
                ];
            }

            $db->getReference('trips/'.$trip->id)->update($payload);
        } catch (Throwable $e) {
            Log::warning('firebase.trip_sync_failed', ['trip_id' => $trip->id, 'message' => $e->getMessage()]);
        }
    }

    public function syncDriverPresence(Driver $driver): void
    {
        $db = $this->database();
        if (! $db) {
            return;
        }

        try {
            $vehicleTypeSlug = $driver->vehicles()->with('vehicleType')->first()?->vehicleType?->slug;
            $presence = $driver->presence instanceof DriverPresence
                ? $driver->presence->value
                : ($driver->is_online ? DriverPresence::Online->value : DriverPresence::Offline->value);

            $db->getReference('drivers/'.$driver->id)->update([
                'lat' => $driver->last_latitude !== null ? (float) $driver->last_latitude : null,
                'lng' => $driver->last_longitude !== null ? (float) $driver->last_longitude : null,
                'presence' => $presence,
                'vehicle_type' => $vehicleTypeSlug,
                'updated_at' => now()->toIso8601String(),
            ]);
        } catch (Throwable $e) {
            Log::warning('firebase.driver_sync_failed', ['driver_id' => $driver->id, 'message' => $e->getMessage()]);
        }
    }

    private function progressLabel(TripStatus $status): string
    {
        return match ($status) {
            TripStatus::Requested => 'searching_driver',
            TripStatus::Accepted => 'driver_assigned',
            TripStatus::DriverArriving => 'driver_en_route',
            TripStatus::CargoLoaded => 'cargo_loaded',
            TripStatus::TripStarted => 'trip_started',
            TripStatus::InTransit => 'in_transit',
            TripStatus::Delivered => 'delivered',
            TripStatus::Cancelled => 'cancelled',
        };
    }
}
