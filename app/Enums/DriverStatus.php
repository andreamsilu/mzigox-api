<?php

declare(strict_types=1);

namespace App\Enums;

enum DriverStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Suspended = 'suspended';
}
