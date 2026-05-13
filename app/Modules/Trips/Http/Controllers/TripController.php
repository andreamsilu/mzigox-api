<?php

declare(strict_types=1);

namespace App\Modules\Trips\Http\Controllers;

use App\Enums\TripStatus;
use App\Helpers\ApiResponse;
use App\Modules\Trips\Http\Requests\TripAcceptRequest;
use App\Modules\Trips\Http\Requests\TripCancelRequest;
use App\Modules\Trips\Http\Requests\TripStatusUpdateRequest;
use App\Modules\Trips\Http\Requests\TripStoreRequest;
use App\Modules\Trips\Http\Resources\TripResource;
use App\Modules\Trips\Models\Trip;
use App\Services\TripService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class TripController
{
    public function __construct(
        private readonly TripService $tripService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Trip::query()->orderByDesc('created_at');

        if ($user->isCustomer()) {
            $query->where('customer_id', $user->id);
        } elseif ($user->isDriver()) {
            $query->where('driver_id', $user->id);
        }

        $trips = $query->limit(50)->get();

        return ApiResponse::success(TripResource::collection($trips)->resolve());
    }

    public function store(TripStoreRequest $request): JsonResponse
    {
        $trip = $this->tripService->createTrip($request->user(), $request->validated());

        return ApiResponse::success((new TripResource($trip))->resolve(), 'Trip created.', 201);
    }

    public function show(Request $request, Trip $trip): JsonResponse
    {
        Gate::authorize('view', $trip);

        return ApiResponse::success((new TripResource($trip))->resolve());
    }

    public function accept(TripAcceptRequest $request, Trip $trip): JsonResponse
    {
        Gate::authorize('accept', $trip);
        $updated = $this->tripService->acceptTrip($request->user(), $trip, $request->validated('vehicle_id'));

        return ApiResponse::success((new TripResource($updated))->resolve(), 'Trip accepted.');
    }

    public function updateStatus(TripStatusUpdateRequest $request, Trip $trip): JsonResponse
    {
        Gate::authorize('updateStatus', $trip);
        $raw = $request->validated('status');
        $status = $raw instanceof TripStatus ? $raw : TripStatus::from((string) $raw);
        $updated = $this->tripService->advanceStatus($trip, $status, $request->user());

        return ApiResponse::success((new TripResource($updated))->resolve(), 'Trip status updated.');
    }

    public function cancel(TripCancelRequest $request, Trip $trip): JsonResponse
    {
        Gate::authorize('cancel', $trip);
        $updated = $this->tripService->cancelTrip($trip, $request->user(), $request->validated('reason'));

        return ApiResponse::success((new TripResource($updated))->resolve(), 'Trip cancelled.');
    }
}
