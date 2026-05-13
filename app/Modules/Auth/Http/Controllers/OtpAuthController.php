<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Enums\UserRole;
use App\Helpers\ApiResponse;
use App\Modules\Auth\Http\Requests\OtpRequestRequest;
use App\Modules\Auth\Http\Requests\OtpVerifyRequest;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OtpAuthController
{
    public function __construct(
        private readonly OtpService $otpService,
    ) {}

    public function requestOtp(OtpRequestRequest $request): JsonResponse
    {
        $this->otpService->requestOtp($request->validated('phone'));

        return ApiResponse::success([], 'OTP sent to your phone number.');
    }

    public function verify(OtpVerifyRequest $request): JsonResponse
    {
        $data = $request->validated();
        $role = null;
        if (! empty($data['role'])) {
            $raw = $data['role'];
            $role = $raw instanceof UserRole ? $raw : UserRole::from((string) $raw);
        }

        $user = $this->otpService->verifyAndLogin($data['phone'], $data['code'], $role);
        $token = $user->createToken('mobile')->plainTextToken;

        return ApiResponse::success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'phone' => $user->phone,
                'role' => $user->role->value,
                'status' => $user->status->value,
            ],
        ], 'Authentication successful.');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return ApiResponse::failure('Unauthenticated.', 401);
        }

        return ApiResponse::success([
            'id' => $user->id,
            'full_name' => $user->full_name,
            'phone' => $user->phone,
            'email' => $user->email,
            'role' => $user->role->value,
            'status' => $user->status->value,
            'profile_photo' => $user->profile_photo,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return ApiResponse::success([], 'Logged out.');
    }
}
