<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Models\User;
use App\Repositories\AuthRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AuthRepository $authRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authRepository = new AuthRepository();
    }

    public function test_can_register_user()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $response = $this->authRepository->register($userData);

        $this->assertDatabaseHas('users', [
            'email' => $userData['email'],
            'name' => $userData['name']
        ]);

        $this->assertEquals(200, $response->status());
        $this->assertEquals('success', $response->getData()->status);
        $this->assertNotNull($response->getData()->authorization->token);
    }

    public function test_can_login_user()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123')
        ]);

        $response = $this->authRepository->login([
            'email' => $user->email,
            'password' => 'password123'
        ]);

        $this->assertEquals(200, $response->status());
        $this->assertEquals('success', $response->getData()->status);
        $this->assertNotNull($response->getData()->authorization->token);
    }

    public function test_throws_exception_on_invalid_credentials()
    {
        $this->expectException(ValidationException::class);

        $user = User::factory()->create();

        $this->authRepository->login([
            'email' => $user->email,
            'password' => 'wrong_password'
        ]);
    }

    public function test_can_logout_user()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $response = $this->authRepository->logout();

        $this->assertEquals(200, $response->status());
        $this->assertEquals('success', $response->getData()->status);
        $this->assertNull(Auth::user());
    }

    public function test_can_refresh_token()
    {
        $user = User::factory()->create();
        $token = Auth::login($user);

        $response = $this->authRepository->refresh();

        $this->assertEquals(200, $response->status());
        $this->assertEquals('success', $response->getData()->status);
        $this->assertNotNull($response->getData()->authorization->token);
        $this->assertNotEquals($token, $response->getData()->authorization->token);
    }
}
