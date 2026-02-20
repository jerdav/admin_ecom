<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_access_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'StrongP@ssword12',
            'password_confirmation' => 'StrongP@ssword12',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'token_type',
                'access_token',
                'user' => ['id', 'name', 'email', 'role'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'role' => User::ROLE_CUSTOMER,
        ]);
    }

    public function test_login_is_rate_limited_after_repeated_attempts(): void
    {
        User::factory()->create([
            'email' => 'rate@example.com',
            'password' => 'ValidP@ssword12',
            'role' => User::ROLE_CUSTOMER,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'rate@example.com',
                'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        $this->postJson('/api/auth/login', [
            'email' => 'rate@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }
}
