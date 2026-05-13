<?php

declare(strict_types=1);

namespace App\Modules\Trips\Http\Resources;

use App\Modules\Trips\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Trip */
class TripResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'driver_id' => $this->driver_id,
            'vehicle_id' => $this->vehicle_id,
            'vehicle_type_id' => $this->vehicle_type_id,
            'pickup_location' => $this->pickup_location,
            'destination_location' => $this->destination_location,
            'cargo_description' => $this->cargo_description,
            'cargo_photo' => $this->cargo_photo,
            'estimated_price_minor' => $this->estimated_price_minor,
            'final_price_minor' => $this->final_price_minor,
            'trip_status' => $this->trip_status->value,
            'payment_status' => $this->payment_status->value,
            'commission_amount_minor' => $this->commission_amount_minor,
            'commission_state' => $this->commission_state->value,
            'cancellation_reason' => $this->cancellation_reason,
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
