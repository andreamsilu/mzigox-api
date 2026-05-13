<?php

declare(strict_types=1);

namespace App\Modules\Trips\Models;

use App\Enums\CommissionState;
use App\Enums\TripPaymentStatus;
use App\Enums\TripStatus;
use App\Modules\Users\Models\User;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trip extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'driver_id',
        'vehicle_id',
        'vehicle_type_id',
        'pickup_location',
        'destination_location',
        'cargo_description',
        'cargo_photo',
        'estimated_price_minor',
        'final_price_minor',
        'trip_status',
        'payment_status',
        'commission_amount_minor',
        'commission_state',
        'cancellation_reason',
        'accepted_at',
        'started_at',
        'completed_at',
        'cancelled_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pickup_location' => 'array',
            'destination_location' => 'array',
            'trip_status' => TripStatus::class,
            'payment_status' => TripPaymentStatus::class,
            'commission_state' => CommissionState::class,
            'estimated_price_minor' => 'integer',
            'final_price_minor' => 'integer',
            'commission_amount_minor' => 'integer',
            'accepted_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(TripStatusLog::class);
    }

    public function tripStarted(): bool
    {
        return in_array($this->trip_status, [
            TripStatus::TripStarted,
            TripStatus::InTransit,
            TripStatus::Delivered,
        ], true);
    }
}
