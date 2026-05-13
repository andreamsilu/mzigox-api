<?php

declare(strict_types=1);

namespace App\Modules\Trips\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TripStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'vehicle_type_id' => ['required', 'uuid', 'exists:vehicle_types,id'],
            'pickup_location' => ['required', 'array'],
            'pickup_location.lat' => ['required', 'numeric'],
            'pickup_location.lng' => ['required', 'numeric'],
            'pickup_location.label' => ['nullable', 'string', 'max:255'],
            'destination_location' => ['required', 'array'],
            'destination_location.lat' => ['required', 'numeric'],
            'destination_location.lng' => ['required', 'numeric'],
            'destination_location.label' => ['nullable', 'string', 'max:255'],
            'cargo_description' => ['nullable', 'string', 'max:2000'],
            'cargo_photo' => ['nullable', 'string', 'max:2048'],
            'estimated_price_minor' => ['required', 'integer', 'min:1'],
        ];
    }
}
