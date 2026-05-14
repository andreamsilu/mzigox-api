<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_mysql')]
class SanctumTokenTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_can_create_sanctum_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $this->assertNotEmpty($token);
    }
}
