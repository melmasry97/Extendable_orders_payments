<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Models\User;
use App\Repositories\AuthRepository;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AuthRepository $authRepository;
    private array $userData;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authRepository = app(AuthRepository::class);

        $this->userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $this->user = User::factory()->create([
            'email' => $this->userData['email'],
            'password' => bcrypt($this->userData['password'])
        ]);
    }

    public function test_register_creates_new_user(): void
    {
        $newUserData = [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123'
        ];

        $user = $this->authRepository->register($newUserData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($newUserData['name'], $user->name);
        $this->assertEquals($newUserData['email'], $user->email);
    }

    public function test_login_returns_user_with_valid_credentials(): void
    {
        $user = $this->authRepository->login([
            'email' => $this->userData['email'],
            'password' => $this->userData['password']
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($this->user->id, $user->id);
    }

    public function test_login_throws_exception_with_invalid_credentials(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid credentials');

        $this->authRepository->login([
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword'
        ]);
    }

    public function test_logout_returns_true_when_user_is_authenticated(): void
    {
        $this->actingAs($this->user);

        $result = $this->authRepository->logout();

        $this->assertTrue($result);
        $this->assertGuest();
    }

    public function test_logout_returns_false_when_no_user_is_authenticated(): void
    {
        $result = $this->authRepository->logout();

        $this->assertFalse($result);
    }

    public function test_refresh_returns_false_when_no_user_is_authenticated(): void
    {
        $result = $this->authRepository->refresh();

        $this->assertFalse($result);
    }
}
