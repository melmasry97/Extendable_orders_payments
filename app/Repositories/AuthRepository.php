<?php

namespace App\Repositories;

use App\Models\User;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\AuthenticationException;
use App\Repositories\Interfaces\AuthRepositoryInterface;

class AuthRepository implements AuthRepositoryInterface
{
    public function register(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return $user;
    }

    public function login(array $credentials)
    {
        $token = Auth::attempt($credentials);

        return $token;
    }

    public function logout()
    {
        if (!Auth::check()) {
            return false;
        }
        Auth::logout();
        return true;
    }

    public function refresh()
    {
        if (!Auth::check()) {
            throw new AuthenticationException('Unauthenticated.');
        }

        return Auth::refresh();
    }
}
