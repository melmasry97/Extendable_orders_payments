<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Helpers\ResponseHelper;
use Throwable;

class ApiHandler
{
    /**
     * HTTP status codes for common errors
     */
    private const HTTP_CODES = [
        'VALIDATION_ERROR' => 422,
        'AUTHENTICATION_ERROR' => 401,
        'NOT_FOUND' => 404,
        'SERVER_ERROR' => 500
    ];

    /**
     * Handle the exception and return JSON response
     */
    public function handle(Throwable $exception): \Illuminate\Http\JsonResponse
    {
        return match(true) {
            // Handle API exceptions (our custom exceptions)
            $exception instanceof ApiException => $this->handleApiException($exception),

            // Handle validation errors
            $exception instanceof ValidationException => $this->handleValidationException($exception),

            // Handle authentication errors
            $exception instanceof AuthenticationException => $this->handleAuthenticationException($exception),

            // Handle not found errors
            $exception instanceof ModelNotFoundException,
            $exception instanceof NotFoundHttpException => $this->handleNotFoundException($exception),

            // Handle other HTTP exceptions
            $exception instanceof HttpException => $this->handleHttpException($exception),

            // Handle all other exceptions
            default => $this->handleDefaultException($exception)
        };
    }

    /**
     * Handle our custom API exceptions
     */
    private function handleApiException(ApiException $exception): \Illuminate\Http\JsonResponse
    {
        return ResponseHelper::error(
            message: $exception->getMessage(),
            code: $exception->getCode(),
            errors: $exception->getErrors()
        );
    }

    /**
     * Handle validation exceptions
     */
    private function handleValidationException(ValidationException $exception): \Illuminate\Http\JsonResponse
    {
        return ResponseHelper::error(
            message: 'Validation failed',
            code: self::HTTP_CODES['VALIDATION_ERROR'],
            errors: $exception->errors()
        );
    }

    /**
     * Handle authentication exceptions
     */
    private function handleAuthenticationException(AuthenticationException $exception): \Illuminate\Http\JsonResponse
    {
        return ResponseHelper::error(
            message: 'Unauthenticated',
            code: self::HTTP_CODES['AUTHENTICATION_ERROR']
        );
    }

    /**
     * Handle not found exceptions
     */
    private function handleNotFoundException(Throwable $exception): \Illuminate\Http\JsonResponse
    {
        $message = $exception instanceof ModelNotFoundException
            ? 'Resource not found'
            : 'Route not found';

        return ResponseHelper::error(
            message: $message,
            code: self::HTTP_CODES['NOT_FOUND']
        );
    }

    /**
     * Handle HTTP exceptions
     */
    private function handleHttpException(HttpException $exception): \Illuminate\Http\JsonResponse
    {
        return ResponseHelper::error(
            message: $exception->getMessage() ?: 'HTTP Error',
            code: $exception->getStatusCode()
        );
    }

    /**
     * Handle all other exceptions
     */
    private function handleDefaultException(Throwable $exception): \Illuminate\Http\JsonResponse
    {
        // Log unexpected errors
        if (!config('app.debug')) {
            \Log::error($exception);
            return ResponseHelper::error(
                message: 'Server Error',
                code: self::HTTP_CODES['SERVER_ERROR']
            );
        }

        // In debug mode, return detailed error
        return ResponseHelper::error(
            message: $exception->getMessage(),
            code: self::HTTP_CODES['SERVER_ERROR'],
            errors: [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ]
        );
    }
}
