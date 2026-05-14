<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\OtpService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_mysql')]
class OtpAuthFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_otp_request_persists_hashed_code(): void
    {
        app(OtpService::class)->requestOtp('+255755555555');

        $this->assertDatabaseHas('phone_otps', ['phone' => '+255755555555']);
    }

    public function test_otp_verify_creates_user(): void
    {
        $service = app(OtpService::class);
        $phone = '+255766666666';
        $service->requestOtp($phone);
        $user = $service->verifyAndLogin($phone, '123456');

        $this->assertSame($phone, $user->phone);
    }

    public function test_otp_verify_api_returns_bearer_token(): void
    {
        $phone = '+255777777777';

        $this->postJson('/api/v1/auth/otp/request', ['phone' => $phone])->assertOk();

        $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => $phone,
            'code' => '123456',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'role']]]);
    }
}
