<?php

declare(strict_types=1);

namespace App\Modules\Trips\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TripAcceptRequest extends FormRequest
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
            'vehicle_id' => ['required', 'uuid', 'exists:vehicles,id'],
        ];
    }
}
