<?php

declare(strict_types=1);

namespace App\Modules\Drivers\Models;

use App\Enums\DriverStatus;
use App\Modules\Users\Models\User;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'status',
        'rating_avg',
        'rating_count',
        'is_online',
        'last_online_at',
        'last_latitude',
        'last_longitude',
        'last_location_at',
        'min_wallet_balance_minor',
        'kyc_payload',
        'approved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DriverStatus::class,
            'is_online' => 'boolean',
            'last_online_at' => 'datetime',
            'last_location_at' => 'datetime',
            'approved_at' => 'datetime',
            'kyc_payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}
