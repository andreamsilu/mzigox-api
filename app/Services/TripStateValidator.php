<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TripStatus;
use App\Exceptions\DomainException;

final class TripStateValidator
{
    /** @var array<string, list<string>> */
    private const array ALLOWED = [
        'REQUESTED' => ['ACCEPTED', 'CANCELLED'],
        'ACCEPTED' => ['DRIVER_ARRIVING', 'CANCELLED'],
        'DRIVER_ARRIVING' => ['CARGO_LOADED', 'CANCELLED'],
        'CARGO_LOADED' => ['TRIP_STARTED', 'CANCELLED'],
        'TRIP_STARTED' => ['IN_TRANSIT', 'CANCELLED'],
        'IN_TRANSIT' => ['DELIVERED', 'CANCELLED'],
        'DELIVERED' => [],
        'CANCELLED' => [],
    ];

    public function assertCanTransition(TripStatus $from, TripStatus $to): void
    {
        if (! $this->canTransition($from, $to)) {
            throw new DomainException(sprintf(
                'Invalid trip transition from %s to %s',
                $from->value,
                $to->value
            ));
        }
    }

    public function canTransition(TripStatus $from, TripStatus $to): bool
    {
        $allowed = self::ALLOWED[$from->value] ?? [];

        return in_array($to->value, $allowed, true);
    }
}
