<?php

namespace Tests\Feature\API\V1;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'user' => ['id', 'name', 'email'],
                'authorization' => ['token', 'type']
            ]);

        $this->assertDatabaseHas('users', [
            'email' => $userData['email'],
            'name' => $userData['name']
        ]);
    }

    public function test_user_cannot_register_with_existing_email()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => $user->email,
            'password' => 'password123'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123')
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'user',
                'authorization' => ['token', 'type']
            ]);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong_password'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Successfully logged out'
            ]);
    }

    public function test_user_can_refresh_token()
    {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'user',
                'authorization' => ['token', 'type']
            ]);
    }

    public function test_unauthenticated_user_cannot_access_protected_routes()
    {
        $response = $this->postJson('/api/v1/auth/logout');
        $response->assertStatus(401);

        $response = $this->postJson('/api/v1/auth/refresh');
        $response->assertStatus(401);
    }
}
