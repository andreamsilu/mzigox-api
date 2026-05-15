<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DriverPresence;
use App\Jobs\SyncDriverPresenceToFirebaseJob;
use App\Modules\Drivers\Models\Driver;

/**
 * Manages operational presence in PostgreSQL (matching) and queues Firebase sync.
 * Live GPS is written by the driver app directly to RTDB every 3–5s.
 */
final class DriverPresenceService
{
    public function setPresence(Driver $driver, DriverPresence $presence): void
    {
        $driver->presence = $presence;
        $driver->is_online = $presence !== DriverPresence::Offline;
        $driver->last_online_at = now();
        $driver->save();

        SyncDriverPresenceToFirebaseJob::dispatch($driver->id)->onQueue('firebase');
    }

    public function markBusy(Driver $driver): void
    {
        $this->setPresence($driver, DriverPresence::Busy);
    }

    public function releaseAfterTrip(Driver $driver): void
    {
        $target = $driver->is_online ? DriverPresence::Online : DriverPresence::Offline;
        $this->setPresence($driver, $target);
    }
}
