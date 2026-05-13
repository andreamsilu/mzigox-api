<?php

declare(strict_types=1);

namespace App\Modules\Firebase\Services;

use App\Modules\Drivers\Models\Driver;
use App\Modules\Trips\Models\Trip;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Database;
use Throwable;

/**
 * Syncs only non-financial realtime fields to Firebase Realtime Database (Laravel remains source of truth).
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

            $db->getReference('trips/'.$trip->id)->set($payload);
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

            $db->getReference('drivers/'.$driver->id)->set([
                'lat' => $driver->last_latitude !== null ? (float) $driver->last_latitude : null,
                'lng' => $driver->last_longitude !== null ? (float) $driver->last_longitude : null,
                'online' => $driver->is_online,
                'vehicle_type' => $vehicleTypeSlug,
                'updated_at' => now()->toIso8601String(),
            ]);
        } catch (Throwable $e) {
            Log::warning('firebase.driver_sync_failed', ['driver_id' => $driver->id, 'message' => $e->getMessage()]);
        }
    }
}
