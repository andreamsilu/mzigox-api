<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OtpVerifyRequest extends FormRequest
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
            'phone' => ['required', 'string', 'min:8', 'max:32'],
            'code' => ['required', 'string', 'size:6'],
            'role' => ['nullable', Rule::enum(UserRole::class)],
        ];
    }
}
