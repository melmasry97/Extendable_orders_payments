<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Repositories\Interfaces\AuthRepositoryInterface;
use App\Helpers\ResponseHelper;

class AuthController extends Controller
{
    protected $authRepository;

    public function __construct(AuthRepositoryInterface $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    public function register(RegisterRequest $request)
    {
        $user = $this->authRepository->register($request->validated());
        $token = auth()->login($user);

        return ResponseHelper::authSuccess($user, $token, 'User created successfully');
    }

    public function login(LoginRequest $request)
    {
        $token = $this->authRepository->login($request->validated());
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
