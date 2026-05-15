<?php

declare(strict_types=1);

namespace App\Modules\Drivers\Http\Controllers;

use App\Enums\DriverPresence;
use App\Helpers\ApiResponse;
use App\Jobs\SyncDriverPresenceToFirebaseJob;
use App\Modules\Drivers\Http\Requests\DriverLocationUpdateRequest;
use App\Modules\Drivers\Http\Requests\DriverOnlineUpdateRequest;
use App\Services\DriverPresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DriverProfileController
{
    public function __construct(
        private readonly DriverPresenceService $presenceService,
    ) {}

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $driver = $user->driver;
        if (! $driver) {
            return ApiResponse::failure('Driver profile not found.', 404);
        }

        return ApiResponse::success([
            'id' => $driver->id,
            'user_id' => $driver->user_id,
            'status' => $driver->status->value,
            'presence' => $driver->presence?->value ?? DriverPresence::Offline->value,
            'is_online' => $driver->is_online,
            'rating_avg' => $driver->rating_avg,
            'rating_count' => $driver->rating_count,
            'last_latitude' => $driver->last_latitude,
            'last_longitude' => $driver->last_longitude,
            'last_location_at' => $driver->last_location_at?->toIso8601String(),
            'firebase' => [
                'driver_node' => 'drivers/'.$driver->id,
                'gps_interval_seconds' => config('firebase-realtime.driver_gps_interval_seconds'),
                'note' => 'Write live GPS directly to Firebase RTDB; this API stores periodic snapshots for matching.',
            ],
        ]);
    }

    public function updateOnline(DriverOnlineUpdateRequest $request): JsonResponse
    {
        $driver = $request->user()->driver;
        if (! $driver) {
            return ApiResponse::failure('Driver profile not found.', 404);
        }

        $presence = $request->filled('presence')
            ? DriverPresence::from($request->validated('presence'))
            : ($request->boolean('is_online') ? DriverPresence::Online : DriverPresence::Offline);

        if ($driver->presence === DriverPresence::Busy && $presence === DriverPresence::Online) {
            return ApiResponse::failure('Cannot go ONLINE while on an active trip. Complete or cancel the trip first.', 422);
        }

        $this->presenceService->setPresence($driver, $presence);

        return ApiResponse::success([
            'presence' => $driver->fresh()->presence->value,
            'is_online' => $driver->is_online,
        ], 'Presence updated.');
    }

    /**
     * Periodic location snapshot for the matching engine (Haversine).
     * High-frequency live GPS must be written by the driver app to Firebase RTDB.
     */
    public function updateLocation(DriverLocationUpdateRequest $request): JsonResponse
    {
        $driver = $request->user()->driver;
        if (! $driver) {
            return ApiResponse::failure('Driver profile not found.', 404);
        }

        $driver->last_latitude = $request->validated('lat');
        $driver->last_longitude = $request->validated('lng');
        $driver->last_location_at = now();
        $driver->save();

        SyncDriverPresenceToFirebaseJob::dispatch($driver->id)->onQueue('firebase');

        return ApiResponse::success([], 'Location snapshot saved for matching.');
    }
}
