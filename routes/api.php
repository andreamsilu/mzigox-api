<?php

declare(strict_types=1);

use App\Modules\Admin\Http\Controllers\AdminDriverController;
use App\Modules\Admin\Http\Controllers\AdminReportController;
use App\Modules\Admin\Http\Controllers\AdminTripController;
use App\Modules\Admin\Http\Controllers\AdminWalletController;
use App\Modules\Auth\Http\Controllers\OtpAuthController;
use App\Modules\Drivers\Http\Controllers\DriverProfileController;
use App\Modules\Trips\Http\Controllers\TripController;
use App\Modules\Users\Http\Controllers\UserProfileController;
use App\Modules\Vehicles\Http\Controllers\VehicleTypeController;
use App\Modules\Wallets\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::middleware('throttle:otp')->group(function (): void {
        Route::post('auth/otp/request', [OtpAuthController::class, 'requestOtp']);
        Route::post('auth/otp/verify', [OtpAuthController::class, 'verify']);
    });

    Route::get('vehicle-types', [VehicleTypeController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('auth/me', [OtpAuthController::class, 'me']);
        Route::post('auth/logout', [OtpAuthController::class, 'logout']);

        Route::patch('users/me', [UserProfileController::class, 'update']);
        Route::post('users/me/devices', [UserProfileController::class, 'registerDevice']);

        Route::get('wallets/me', [WalletController::class, 'show']);
        Route::post('wallets/me/topups', [WalletController::class, 'topup']);

        Route::middleware('role:customer')->post('trips', [TripController::class, 'store']);

        Route::get('trips', [TripController::class, 'index']);
        Route::get('trips/{trip}', [TripController::class, 'show']);

        Route::middleware('role:driver,admin')->patch('trips/{trip}/status', [TripController::class, 'updateStatus']);

        Route::middleware('role:driver')->group(function (): void {
            Route::post('trips/{trip}/accept', [TripController::class, 'accept']);
            Route::get('drivers/me', [DriverProfileController::class, 'me']);
            Route::patch('drivers/me/online', [DriverProfileController::class, 'updateOnline']);
            Route::post('drivers/me/location', [DriverProfileController::class, 'updateLocation']);
        });

        Route::middleware('role:customer,driver,admin')->post('trips/{trip}/cancel', [TripController::class, 'cancel']);
    });

    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function (): void {
        Route::get('trips/active', [AdminTripController::class, 'active']);
        Route::post('drivers/{driver}/approve', [AdminDriverController::class, 'approve']);
        Route::get('wallets', [AdminWalletController::class, 'index']);
        Route::get('reports/commission', [AdminReportController::class, 'commission']);
        Route::get('disputes', [AdminReportController::class, 'disputes']);
    });
});
