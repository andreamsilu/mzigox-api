<?php

declare(strict_types=1);

namespace App\Enums;

enum TripStatus: string
{
    case Requested = 'REQUESTED';
    case Accepted = 'ACCEPTED';
    case DriverArriving = 'DRIVER_ARRIVING';
    case CargoLoaded = 'CARGO_LOADED';
    case TripStarted = 'TRIP_STARTED';
    case InTransit = 'IN_TRANSIT';
    case Delivered = 'DELIVERED';
    case Cancelled = 'CANCELLED';
}
