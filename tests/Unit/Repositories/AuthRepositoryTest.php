<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Models\User;
use App\Repositories\AuthRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AuthRepository $authRepository;
    private array $userData;

    public function setUp(): void
    {
        parent::setUp();
        $this->authRepository = new AuthRepository();
        $this->userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];
    }

    public function test_can_register_user()
    {
        $user = $this->authRepository->register($this->userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($this->userData['name'], $user->name);
        $this->assertEquals($this->userData['email'], $user->email);
        $this->assertTrue(Hash::check($this->userData['password'], $user->password));
    }

    public function test_can_login_user()
    {
        // Create user first
        User::create([
            'name' => $this->userData['name'],
            'email' => $this->userData['email'],
            'password' => Hash::make($this->userData['password'])
        ]);

        $token = $this->authRepository->login([
            'email' => $this->userData['email'],
            'password' => $this->userData['password']
        ]);

        $this->assertIsString($token);
        $this->assertTrue(auth()->check());
    }

    public function test_login_fails_with_incorrect_credentials()
    {
        // Create user first
        User::create([
            'name' => $this->userData['name'],
            'email' => $this->userData['email'],
            'password' => Hash::make($this->userData['password'])
        ]);

        $this->assertFalse($this->authRepository->login([
            'email' => $this->userData['email'],
            'password' => 'wrongpassword'
        ]));
    }

    public function test_can_logout_user()
    {
        // Create and login user first
        $user = User::create([
            'name' => $this->userData['name'],
            'email' => $this->userData['email'],
            'password' => Hash::make($this->userData['password'])
        ]);

        auth()->login($user);

        $this->assertTrue($this->authRepository->logout());
        $this->assertFalse(auth()->check());
    }

    public function test_logout_fails_when_not_authenticated()
    {
        $this->assertFalse($this->authRepository->logout());
    }

    public function test_can_refresh_token()
    {
        // Create and login user first
        $user = User::create([
            'name' => $this->userData['name'],
            'email' => $this->userData['email'],
            'password' => Hash::make($this->userData['password'])
        ]);

        auth()->login($user);
        $oldToken = auth()->tokenById($user->id);

        $newToken = $this->authRepository->refresh();
        $this->assertIsString($newToken);
        $this->assertNotEquals($oldToken, $newToken);
    }

    public function test_refresh_fails_when_not_authenticated()
    {
        $this->assertFalse($this->authRepository->refresh());
    }
}
