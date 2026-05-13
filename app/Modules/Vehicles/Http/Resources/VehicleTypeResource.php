<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Http\Resources;

use App\Modules\Vehicles\Models\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin VehicleType */
class VehicleTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'default_capacity_kg' => $this->default_capacity_kg,
        ];
    }
}
