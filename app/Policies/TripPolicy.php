<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Modules\Trips\Models\Trip;
use App\Modules\Users\Models\User;

class TripPolicy
{
    public function view(User $user, Trip $trip): bool
    {
        return $user->role === UserRole::Admin
            || $trip->customer_id === $user->id
            || $trip->driver_id === $user->id;
    }

    public function cancel(User $user, Trip $trip): bool
    {
        return $user->role === UserRole::Admin
            || $trip->customer_id === $user->id
            || $trip->driver_id === $user->id;
    }

    public function accept(User $user, Trip $trip): bool
    {
        return $user->role === UserRole::Driver
            && $trip->customer_id !== $user->id;
    }

    public function updateStatus(User $user, Trip $trip): bool
    {
        return $user->role === UserRole::Admin
            || ($user->role === UserRole::Driver && $trip->driver_id === $user->id);
    }
}
