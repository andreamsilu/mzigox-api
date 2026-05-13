<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\DomainException;
use App\Jobs\SendSmsJob;
use App\Modules\Auth\Models\PhoneOtp;
use App\Modules\Users\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Hash;

final class OtpService
{
    private const int OTP_TTL_MINUTES = 10;

    public function requestOtp(string $phone): void
    {
        $normalized = $this->normalizePhone($phone);
        $code = $this->generateCode();
        $hash = Hash::make($code);

        PhoneOtp::query()->where('phone', $normalized)->delete();

        PhoneOtp::query()->create([
            'phone' => $normalized,
            'code_hash' => $hash,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
        ]);

        SendSmsJob::dispatch($normalized, 'MzigoX login code: '.$code)->onQueue('sms');
    }

    public function verifyAndLogin(string $phone, string $code, ?UserRole $role = null): User
    {
        $normalized = $this->normalizePhone($phone);

        $otp = PhoneOtp::query()->where('phone', $normalized)->orderByDesc('id')->first();
        if (! $otp || $otp->expires_at->isPast()) {
            throw new DomainException('OTP expired or not found. Request a new code.');
        }

        if ($otp->attempts >= 5) {
            throw new DomainException('Too many invalid attempts. Request a new code.');
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');
            throw new DomainException('Invalid OTP code.');
        }

        $otp->delete();

        $user = User::query()->firstOrCreate(
            ['phone' => $normalized],
            [
                'full_name' => 'User '.substr($normalized, -4),
                'role' => $role ?? UserRole::Customer,
                'status' => UserStatus::Active,
            ]
        );

        if ($role !== null && $user->role !== $role) {
            throw new DomainException('This account cannot authenticate with the selected role.');
        }

        return $user;
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return $digits === '' ? $phone : '+'.$digits;
    }

    private function generateCode(): string
    {
        if (! app()->isProduction()) {
            return '123456';
        }

        return str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
    }
}
