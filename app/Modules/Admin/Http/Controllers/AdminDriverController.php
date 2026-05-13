<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Enums\DriverStatus;
use App\Helpers\ApiResponse;
use App\Modules\Drivers\Models\Driver;
use Illuminate\Http\JsonResponse;

final class AdminDriverController
{
    public function approve(Driver $driver): JsonResponse
    {
        $driver->status = DriverStatus::Approved;
        $driver->approved_at = now();
        $driver->save();

        return ApiResponse::success([
            'id' => $driver->id,
            'status' => $driver->status->value,
        ], 'Driver approved.');
    }
}
