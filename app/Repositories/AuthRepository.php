<?php

namespace App\Repositories;

use App\Models\User;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Repositories\Interfaces\AuthRepositoryInterface;

class AuthRepository implements AuthRepositoryInterface
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
        return Auth::attempt($credentials);
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
