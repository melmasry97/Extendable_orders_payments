<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use App\Helpers\ResponseHelper;

class ApiAuthenticate extends Middleware
{
    /**
     * Handle an unauthenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array<string>  $guards
     * @return void
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function unauthenticated($request, array $guards)
    {
        return ResponseHelper::error('Unauthenticated', 401, 'Unauthenticated');
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo($request): ?string
    {
        return null;
    }
}
