<?php

namespace App\Repositories;

use App\Models\User;
use App\Helpers\ResponseHelper;
use App\Repositories\Interfaces\AuthRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthRepository implements AuthRepositoryInterface
{
    public function register(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = Auth::login($user);

        return ResponseHelper::authSuccess($user, $token, 'User created successfully');
    }

    public function login(array $credentials)
    {
        if (!$token = Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return ResponseHelper::authSuccess(Auth::user(), $token);
    }

    public function logout()
    {
        Auth::logout();
        return ResponseHelper::success(message: 'Successfully logged out');
    }

    public function refresh()
    {
        return ResponseHelper::authSuccess(Auth::user(), Auth::refresh());
    }
}
