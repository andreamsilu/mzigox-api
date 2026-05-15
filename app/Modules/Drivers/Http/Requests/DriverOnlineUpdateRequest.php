<?php

declare(strict_types=1);

namespace App\Modules\Drivers\Http\Requests;

use App\Enums\DriverPresence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DriverOnlineUpdateRequest extends FormRequest
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
            'presence' => ['required_without:is_online', Rule::enum(DriverPresence::class)],
            'is_online' => ['required_without:presence', 'boolean'],
        ];
    }
}
