<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Repositories\Interfaces\AuthRepositoryInterface;

class AuthController extends Controller
{
    protected $authRepository;

    public function __construct(AuthRepositoryInterface $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    public function register(RegisterRequest $request) : JsonResponse
    {
        $user = $this->authRepository->register($request->validated());
        $token = auth()->login($user);

        return ResponseHelper::authSuccess($user, $token, 'User created successfully');
    }

    public function login(LoginRequest $request) : JsonResponse
    {
        $token = $this->authRepository->login($request->validated());

        if (!$token) {
            return ResponseHelper::error('Invalid credentials', 401, 'invalid email or password');
        }

        return ResponseHelper::authSuccess(auth()->user(), $token);
    }

    public function logout() : JsonResponse
    {
        $logout = $this->authRepository->logout();
        if (!$logout) {
            return ResponseHelper::error('Unauthenticated', 401, 'Unauthenticated');
        }
        return ResponseHelper::success(message: 'Successfully logged out');
    }

    public function refresh() : JsonResponse
    {
        $token = $this->authRepository->refresh();
        return ResponseHelper::authSuccess(auth()->user(), $token);
    }
}
