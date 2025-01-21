<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiHandler
{
    /**
     * HTTP status codes
     */
    private const HTTP_CODES = [
        'NOT_FOUND' => 404,
        'VALIDATION_ERROR' => 422,
        'UNAUTHORIZED' => 401,
        'FORBIDDEN' => 403,
        'METHOD_NOT_ALLOWED' => 405,
        'INTERNAL_ERROR' => 500,
    ];

    /**
     * Handle API exceptions
     */
    public function handle(Request $request, Throwable $exception): JsonResponse
    {
        return match(true) {
            $this->isNotFoundException($exception) =>
                $this->notFoundResponse($exception),

            $exception instanceof ValidationException =>
                $this->validationErrorResponse($exception),

            $exception instanceof AuthenticationException =>
                $this->errorResponse('Unauthorized access', self::HTTP_CODES['UNAUTHORIZED']),

            $exception instanceof AuthorizationException =>
                $this->errorResponse('Forbidden access', self::HTTP_CODES['FORBIDDEN']),

            $exception instanceof MethodNotAllowedHttpException =>
                $this->errorResponse('Method not allowed', self::HTTP_CODES['METHOD_NOT_ALLOWED']),

            $exception instanceof QueryException =>
                $this->databaseErrorResponse($exception),

            default => $this->fallbackResponse($exception)
        };
    }

    /**
     * Check if exception is a not found exception
     */
    private function isNotFoundException(Throwable $exception): bool
    {
        return $exception instanceof ModelNotFoundException
            || $exception instanceof NotFoundHttpException;
    }

    /**
     * Handle not found exceptions
     */
    private function notFoundResponse(Throwable $exception): JsonResponse
    {
        $message = $exception instanceof ModelNotFoundException
            ? 'Resource not found'
            : 'Route not found';

        return $this->errorResponse($message, self::HTTP_CODES['NOT_FOUND']);
    }

    /**
     * Handle validation exceptions
     */
    private function validationErrorResponse(ValidationException $exception): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $exception->errors()
        ], self::HTTP_CODES['VALIDATION_ERROR']);
    }

    /**
     * Handle database exceptions
     */
    private function databaseErrorResponse(QueryException $exception): JsonResponse
    {
        $message = config('app.debug')
            ? $exception->getMessage()
            : 'Database error occurred';

        return $this->errorResponse($message, self::HTTP_CODES['INTERNAL_ERROR']);
    }

    /**
     * Handle any unhandled exceptions
     */
    private function fallbackResponse(Throwable $exception): JsonResponse
    {
        $statusCode = $this->getStatusCodeFromException($exception);

        $message = config('app.debug')
            ? $exception->getMessage()
            : 'An unexpected error occurred';

        return $this->errorResponse($message, $statusCode);
    }

    /**
     * Get status code from exception
     */
    private function getStatusCodeFromException(Throwable $exception): int
    {
        if ($exception instanceof HttpException) {
            return $exception->getStatusCode();
        }

        return self::HTTP_CODES['INTERNAL_ERROR'];
    }

    /**
     * Return a JSON error response
     */
    private function errorResponse(string $message, int $statusCode): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message
        ], $statusCode);
    }
}
