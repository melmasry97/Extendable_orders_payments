<?php

namespace App\Repositories;

use App\Models\User;
use App\Helpers\ResponseHelper;
use App\Interfaces\AuthInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\AuthenticationException;

class AuthRepository implements AuthInterface
{
    public function register(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    public function login(array $credentials)
    {
        if (!Auth::attempt($credentials)) {
            throw new AuthenticationException('Invalid credentials');
        }
        return Auth::user();
    }

    public function logout() :bool
    {
        if (Auth::check()) {
            Auth::logout();
            return true;
        }
        return false;
    }

    public function refresh()
    {
        return Auth::check() ? Auth::refresh() : false;
    }
}
