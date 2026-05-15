<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\DriverPresence;
use App\Enums\DriverStatus;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Vehicles\Models\VehicleType;
use Illuminate\Support\Collection;

final class DriverMatchingRepository
{
    /**
     * Nearest eligible drivers using Haversine (km). Compatible with PostgreSQL and MySQL.
     *
     * @return Collection<int, Driver>
     */
    public function findEligibleNear(
        float $latitude,
        float $longitude,
        VehicleType $vehicleType,
        int $minWalletAvailableMinor,
        float $radiusKm,
        int $limit = 20,
    ): Collection {
        $haversine = '(6371 * acos(least(1, greatest(-1, cos(radians(?)) * cos(radians(last_latitude)) * cos(radians(last_longitude) - radians(?)) + sin(radians(?)) * sin(radians(last_latitude))))))';

        return Driver::query()
            ->select('drivers.*')
            ->selectRaw("{$haversine} AS distance_km", [$latitude, $longitude, $latitude])
            ->join('users', 'users.id', '=', 'drivers.user_id')
            ->join('wallets', 'wallets.user_id', '=', 'users.id')
            ->join('vehicles', function ($join) use ($vehicleType): void {
                $join->on('vehicles.driver_id', '=', 'drivers.id')
                    ->where('vehicles.vehicle_type_id', '=', $vehicleType->id);
            })
            ->where('drivers.presence', DriverPresence::Online)
            ->where('drivers.status', DriverStatus::Approved)
            ->whereRaw('(wallets.balance_minor - wallets.reserved_balance_minor) >= ?', [$minWalletAvailableMinor])
            ->whereNotNull('drivers.last_latitude')
            ->whereNotNull('drivers.last_longitude')
            ->whereRaw("{$haversine} <= ?", [$latitude, $longitude, $latitude, $radiusKm])
            ->orderByRaw("{$haversine}", [$latitude, $longitude, $latitude])
            ->limit($limit)
            ->get();
    }
}
