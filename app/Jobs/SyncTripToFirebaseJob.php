<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Modules\Firebase\Services\FirebaseRealtimeSyncService;
use App\Modules\Trips\Models\Trip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SyncTripToFirebaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $tripId,
    ) {}

    public function handle(FirebaseRealtimeSyncService $sync): void
    {
        $trip = Trip::query()->find($this->tripId);
        if (! $trip) {
            return;
        }

        $sync->syncTrip($trip);
    }
}
