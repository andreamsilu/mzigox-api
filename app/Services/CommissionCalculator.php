<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CommissionState;
use App\Enums\TripStatus;
use App\Modules\Trips\Models\Trip;

final class CommissionCalculator
{
    /** Commission rate applied to estimated trip price (basis points, 1000 = 10%). */
    private const int BASIS_POINTS = 1000;

    public function estimatedAmountMinor(Trip $trip): int
    {
        return (int) floor($trip->estimated_price_minor * self::BASIS_POINTS / 10000);
    }

    public function amountAfterCancellationMinor(Trip $trip): int
    {
        if (! $trip->tripStarted()) {
            return 0;
        }

        return (int) floor($this->estimatedAmountMinor($trip) / 2);
    }

    public function shouldReserveCommission(TripStatus $status): bool
    {
        return $status === TripStatus::TripStarted;
    }

    public function commissionStateAfterReserve(): CommissionState
    {
        return CommissionState::Reserved;
    }
}
