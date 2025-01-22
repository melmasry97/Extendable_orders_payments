<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Response;
use App\Helpers\ResponseHelper;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        PaymentException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * A list of the exception types that should not be reported.
     *
     * @return bool
     */
    protected function shouldntReport(Throwable $e)
    {
        return parent::shouldntReport($e);
    }

    /**
     * A list of the inputs that should not be flashed for validation exceptions.
     *
     * @return array
     */
    protected function flashable(Throwable $e)
    {
        return parent::flashable($e);
    }

    /**
     * Report or log an exception.
     *
     * @return void
     */
    public function report(Throwable $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Throwable $e)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return app(ApiHandler::class)->handle($e);
        }

        return parent::render($request, $e);
    }
}
