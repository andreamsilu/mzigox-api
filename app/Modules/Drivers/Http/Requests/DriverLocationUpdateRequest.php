<?php

declare(strict_types=1);

namespace App\Modules\Drivers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DriverLocationUpdateRequest extends FormRequest
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
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
        ];
    }
}
