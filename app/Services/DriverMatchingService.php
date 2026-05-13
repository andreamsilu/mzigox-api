<?php

declare(strict_types=1);

namespace App\Services;

use App\Modules\Drivers\Models\Driver;
use App\Modules\Trips\Models\Trip;
use App\Modules\Vehicles\Models\VehicleType;
use App\Repositories\DriverMatchingRepository;
use Illuminate\Support\Collection;

final class DriverMatchingService
{
    public function __construct(
        private readonly DriverMatchingRepository $repository,
    ) {}

    /**
     * @return Collection<int, Driver>
     */
    public function findCandidatesForTrip(Trip $trip, float $radiusKm = 25.0): Collection
    {
        /** @var VehicleType $type */
        $type = VehicleType::query()->findOrFail($trip->vehicle_type_id);

        $pickup = $trip->pickup_location;
        $lat = (float) ($pickup['lat'] ?? 0);
        $lng = (float) ($pickup['lng'] ?? 0);

        $commission = app(CommissionCalculator::class)->estimatedAmountMinor($trip);

        return $this->repository->findEligibleNear($lat, $lng, $type, $commission, $radiusKm);
    }
}
