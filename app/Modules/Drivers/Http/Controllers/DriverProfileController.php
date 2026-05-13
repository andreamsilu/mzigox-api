<?php

declare(strict_types=1);

namespace App\Modules\Drivers\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Jobs\SyncDriverPresenceToFirebaseJob;
use App\Modules\Drivers\Http\Requests\DriverLocationUpdateRequest;
use App\Modules\Drivers\Http\Requests\DriverOnlineUpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DriverProfileController
{
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
            'is_online' => $driver->is_online,
            'rating_avg' => $driver->rating_avg,
            'rating_count' => $driver->rating_count,
            'last_latitude' => $driver->last_latitude,
            'last_longitude' => $driver->last_longitude,
            'last_location_at' => $driver->last_location_at?->toIso8601String(),
        ]);
    }

    public function updateOnline(DriverOnlineUpdateRequest $request): JsonResponse
    {
        $driver = $request->user()->driver;
        if (! $driver) {
            return ApiResponse::failure('Driver profile not found.', 404);
        }

        $driver->is_online = $request->boolean('is_online');
        $driver->last_online_at = now();
        $driver->save();

        SyncDriverPresenceToFirebaseJob::dispatch($driver->id)->onQueue('firebase');

        return ApiResponse::success([
            'is_online' => $driver->is_online,
        ], 'Online status updated.');
    }

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

        return ApiResponse::success([], 'Location updated.');
    }
}
