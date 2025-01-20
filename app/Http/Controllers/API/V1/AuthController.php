<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Repositories\Interfaces\AuthRepositoryInterface;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected $authRepository;

    public function __construct(AuthRepositoryInterface $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $user = $this->authRepository->register($validated);
        $token = auth()->login($user);

        return ResponseHelper::authSuccess($user, $token, 'User created successfully');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $token = $this->authRepository->login($validated);
        return ResponseHelper::authSuccess(auth()->user(), $token);
    }

    public function logout()
    {
        $this->authRepository->logout();
        return ResponseHelper::success(message: 'Successfully logged out');
    }

    public function refresh()
    {
        $token = $this->authRepository->refresh();
        return ResponseHelper::authSuccess(auth()->user(), $token);
    }
}
