<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Modules\Trips\Http\Resources\TripResource;
use App\Repositories\TripRepository;
use Illuminate\Http\JsonResponse;

final class AdminTripController
{
    public function __construct(
        private readonly TripRepository $tripRepository,
    ) {}

    public function active(): JsonResponse
    {
        $trips = $this->tripRepository->activeForAdmin();

        return ApiResponse::success(TripResource::collection($trips)->resolve());
    }
}
