<?php

namespace Tests\Feature\Controllers\API\V1;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    private array $validUserData;

    public function setUp(): void
    {
        parent::setUp();

        $this->validUserData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];
    }

    public function test_user_can_register_with_valid_data()
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validUserData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'user' => ['id', 'name', 'email'],
                'authorization' => ['token', 'type']
            ]);

        $this->assertDatabaseHas('users', [
            'email' => $this->validUserData['email'],
            'name' => $this->validUserData['name']
        ]);
    }

    public function test_register_validates_required_fields()
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_register_validates_email_format()
    {
        $data = $this->validUserData;
        $data['email'] = 'invalid-email';

        $response = $this->postJson('/api/v1/auth/register', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_prevents_duplicate_email()
    {
        // Create first user
        User::create($this->validUserData);

        // Try to register with same email
        $response = $this->postJson('/api/v1/auth/register', $this->validUserData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_validates_password_length()
    {
        $data = $this->validUserData;
        $data['password'] = '12345'; // Less than minimum

        $response = $this->postJson('/api/v1/auth/register', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_user_can_login_with_valid_credentials()
    {
        // Create user
        User::create([
            'name' => $this->validUserData['name'],
            'email' => $this->validUserData['email'],
            'password' => bcrypt($this->validUserData['password'])
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $this->validUserData['email'],
            'password' => $this->validUserData['password']
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'user',
                'authorization' => ['token', 'type']
            ]);
    }

    public function test_login_validates_required_fields()
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_validates_email_format()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'invalid-email',
            'password' => 'password123'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_with_invalid_credentials()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ]);
    }

    public function test_user_can_logout_when_authenticated()
    {
        // Create and login user
        $user = User::create([
            'name' => $this->validUserData['name'],
            'email' => $this->validUserData['email'],
            'password' => bcrypt($this->validUserData['password'])
        ]);

        $token = auth()->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Successfully logged out'
            ]);
    }

    public function test_logout_fails_when_not_authenticated()
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    public function test_user_can_refresh_token()
    {
        // Create and login user
        $user = User::create([
            'name' => $this->validUserData['name'],
            'email' => $this->validUserData['email'],
            'password' => bcrypt($this->validUserData['password'])
        ]);

        $token = auth()->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'user',
                'authorization' => ['token', 'type']
            ]);

        $this->assertNotEquals($token, $response->json('authorization.token'));
    }

    public function test_refresh_fails_when_not_authenticated()
    {
        $response = $this->postJson('/api/v1/auth/refresh');

        $response->assertStatus(401);
    }
}
