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
        return auth()->login($user)
            ? ResponseHelper::authSuccess($user, auth()->tokenById($user->id), 'User created successfully')
            : ResponseHelper::error('Unauthenticated', 401, 'Unauthenticated');
    }

    public function login(LoginRequest $request) : JsonResponse
    {
        return $this->authRepository->login($request->validated())
            ? ResponseHelper::authSuccess($user = auth()->user(), auth()->tokenById($user->id))
            : ResponseHelper::error('Invalid credentials', 401, 'invalid email or password');
    }

    public function logout() : JsonResponse
    {
        return $this->authRepository->logout()
            ? ResponseHelper::success(message: 'Successfully logged out')
            : ResponseHelper::error('Unauthenticated', 401, 'Unauthenticated');
    }

    public function refresh() : JsonResponse
    {
        return $this->authRepository->refresh()
            ? ResponseHelper::authSuccess(auth()->user(), auth()->refresh())
            : ResponseHelper::error('Unauthenticated', 401, 'Unauthenticated');
    }
}
