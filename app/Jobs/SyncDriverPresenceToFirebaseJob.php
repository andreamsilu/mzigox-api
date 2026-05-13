<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Modules\Drivers\Models\Driver;
use App\Modules\Firebase\Services\FirebaseRealtimeSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SyncDriverPresenceToFirebaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $driverId,
    ) {}

    public function handle(FirebaseRealtimeSyncService $sync): void
    {
        $driver = Driver::query()->find($this->driverId);
        if (! $driver) {
            return;
        }

        $sync->syncDriverPresence($driver);
    }
}
