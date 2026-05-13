<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class OtpAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_verify_returns_bearer_token(): void
    {
        $this->postJson('/api/v1/auth/otp/request', ['phone' => '+255733333333'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => '+255733333333',
            'code' => '123456',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['token', 'token_type', 'user' => ['id', 'role']],
            ]);
    }
}
