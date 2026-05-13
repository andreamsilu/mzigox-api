<?php

declare(strict_types=1);

namespace App\Enums;

enum CommissionState: string
{
    case None = 'none';
    case Reserved = 'reserved';
    case Finalized = 'finalized';
    case Released = 'released';
}
