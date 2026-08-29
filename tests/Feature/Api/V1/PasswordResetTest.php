<?php

namespace Tests\Feature\Api\V1;

use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(AuthService::class)->registerOwner([
            'business_name' => 'NIRA Test Business',
            'full_name' => 'Willy Permana',
            'email' => 'owner@nirapos.id',
            'password' => 'old-secure-password',
        ]);
    }

    public function test_user_can_request_password_reset_link(): void
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'owner@nirapos.id',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'owner@nirapos.id',
        ]);
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $authService = app(AuthService::class);
        $result = $authService->sendPasswordResetLink('owner@nirapos.id');
        $token = $result['token'];

        // Verify token endpoint
        $verifyResponse = $this->postJson('/api/auth/verify-reset-token', [
            'email' => 'owner@nirapos.id',
            'token' => $token,
        ]);
        $verifyResponse->assertStatus(200);

        // Perform password reset
        $resetResponse = $this->postJson('/api/auth/reset-password', [
            'email' => 'owner@nirapos.id',
            'token' => $token,
            'password' => 'new-secure-password-123',
        ]);

        $resetResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Login with new password
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'owner@nirapos.id',
            'password' => 'new-secure-password-123',
        ]);

        $loginResponse->assertStatus(200);
    }
}
