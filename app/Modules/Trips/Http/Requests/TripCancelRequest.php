<?php

declare(strict_types=1);

namespace App\Modules\Trips\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TripCancelRequest extends FormRequest
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
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
