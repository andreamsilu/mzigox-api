<?php

declare(strict_types=1);

namespace App\Enums;

enum TripPaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Pending = 'pending';
    case Paid = 'paid';
    case Refunded = 'refunded';
}
