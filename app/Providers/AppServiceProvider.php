<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Trips\Models\Trip;
use App\Modules\Users\Models\User;
use App\Policies\TripPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Relation::enforceMorphMap([
            'trip' => Trip::class,
            'user' => User::class,
        ]);

        Gate::policy(Trip::class, TripPolicy::class);

        RateLimiter::for('otp', function ($request) {
            return Limit::perMinute(6)->by($request->ip());
        });
    }
}
