<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Repositories\AuthRepository;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Interfaces\AuthInterface;

class AuthController extends Controller
{
    protected $authInterface;

    public function __construct(AuthInterface $authInterface)
    {
        $this->authInterface = $authInterface;
    }

    public function register(RegisterRequest $request) : JsonResponse
    {
        $user = $this->authInterface->register($request->validated());
        return auth()->login($user)
            ? ResponseHelper::authSuccess($user, auth()->tokenById($user->id), 'User created successfully')
            : ResponseHelper::error('Unauthenticated', 401, 'Unauthenticated');
    }

    public function login(LoginRequest $request) : JsonResponse
    {
        return $this->authInterface->login($request->validated())
            ? ResponseHelper::authSuccess($user = auth()->user(), auth()->tokenById($user->id))
            : ResponseHelper::error('Invalid credentials', 401, 'invalid email or password');
    }

    public function logout() : JsonResponse
    {
        return $this->authInterface->logout()
            ? ResponseHelper::success(message: 'Successfully logged out')
            : ResponseHelper::error('Unauthenticated', 401, 'Unauthenticated');
    }

    public function refresh() : JsonResponse
    {
        return $this->authInterface->refresh()
            ? ResponseHelper::authSuccess(auth()->user(), auth()->refresh())
            : ResponseHelper::error('Unauthenticated', 401, 'Unauthenticated');
    }
}
