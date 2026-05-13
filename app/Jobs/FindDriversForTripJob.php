<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\TripStatus;
use App\Modules\Trips\Models\Trip;
use App\Services\DriverMatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class FindDriversForTripJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $tripId,
    ) {}

    public function handle(DriverMatchingService $matching): void
    {
        $trip = Trip::query()->find($this->tripId);
        if (! $trip || $trip->trip_status !== TripStatus::Requested) {
            return;
        }

        $drivers = $matching->findCandidatesForTrip($trip);
        Log::info('matching.trip_candidates', [
            'trip_id' => $trip->id,
            'count' => $drivers->count(),
            'driver_ids' => $drivers->pluck('id')->all(),
        ]);
    }
}
