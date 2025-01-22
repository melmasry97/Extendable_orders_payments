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

    public function __construct(protected AuthInterface $authInterface)
    {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authInterface->register($request->validated());
        return ResponseHelper::authSuccess($user, auth()->tokenById($user->id), 'User created successfully');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->authInterface->login($request->validated());
        return ResponseHelper::authSuccess($user, auth()->tokenById($user->id), 'Successfully logged in');
    }

    public function logout(): JsonResponse
    {
        $this->authInterface->logout();
        return ResponseHelper::success(message: 'Successfully logged out');
    }

    public function refresh(): JsonResponse
    {
        $this->authInterface->refresh();
        return ResponseHelper::authSuccess(auth()->user(), auth()->refresh());
    }
}
