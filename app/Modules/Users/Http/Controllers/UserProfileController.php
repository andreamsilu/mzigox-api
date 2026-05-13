<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Modules\Users\Http\Requests\UserDeviceStoreRequest;
use App\Modules\Users\Http\Requests\UserProfileUpdateRequest;
use App\Modules\Users\Models\UserDevice;
use Illuminate\Http\JsonResponse;

final class UserProfileController
{
    public function update(UserProfileUpdateRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->fill($request->validated());
        $user->save();

        return ApiResponse::success([
            'id' => $user->id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'profile_photo' => $user->profile_photo,
        ], 'Profile updated.');
    }

    public function registerDevice(UserDeviceStoreRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $device = UserDevice::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'device_id' => $data['device_id'] ?? 'default',
            ],
            [
                'fcm_token' => $data['fcm_token'] ?? null,
                'platform' => $data['platform'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'last_seen_at' => now(),
            ]
        );

        return ApiResponse::success([
            'id' => $device->id,
            'device_id' => $device->device_id,
        ], 'Device registered.');
    }
}
