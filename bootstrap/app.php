<?php

use App\Exceptions\ApiHandler;
use App\Exceptions\PaymentException;
use Illuminate\Foundation\Application;
use App\Http\Middleware\ApiAuthenticate;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth.api' => ApiAuthenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Don't report payment exceptions
        $exceptions->dontReport(PaymentException::class);

        // Handle API exceptions
        $exceptions->renderable(function (Throwable $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return app(ApiHandler::class)->handle($e);
            }
        });

        // Custom reporting for specific exceptions
        $exceptions->reportable(function (PaymentException $e) {
            // Log payment errors if needed
            if ($e->getCode() >= 500) {
                \Log::error('Payment System Error', [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'errors' => $e->getErrors()
                ]);
            }
            return false;
        });
    })->create();
