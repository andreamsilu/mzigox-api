<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Modules\Vehicles\Http\Resources\VehicleTypeResource;
use App\Modules\Vehicles\Models\VehicleType;
use Illuminate\Http\JsonResponse;

final class VehicleTypeController
{
    public function index(): JsonResponse
    {
        $types = VehicleType::query()->where('is_active', true)->orderBy('name')->get();

        return ApiResponse::success(VehicleTypeResource::collection($types)->resolve());
    }
}
