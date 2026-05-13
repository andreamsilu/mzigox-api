<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\TripStatus;
use App\Modules\Trips\Models\Trip;
use Illuminate\Database\Eloquent\Collection;

final class TripRepository
{
    public function findOrFail(string $id): Trip
    {
        return Trip::query()->findOrFail($id);
    }

    /**
     * @return Collection<int, Trip>
     */
    public function activeForAdmin(): Collection
    {
        return Trip::query()
            ->whereNotIn('trip_status', [TripStatus::Delivered, TripStatus::Cancelled])
            ->with(['customer', 'driver', 'vehicleType'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();
    }
}
