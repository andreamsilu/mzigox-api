<?php

declare(strict_types=1);

namespace App\Enums;

enum DriverPresence: string
{
    case Online = 'ONLINE';
    case Offline = 'OFFLINE';
    case Busy = 'BUSY';
}
