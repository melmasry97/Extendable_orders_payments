<?php

namespace App\Exceptions;

use Exception;

class PaymentException extends ApiException
{
    public const ORDER_NOT_CONFIRMED = 422;
    public const GATEWAY_NOT_FOUND = 404;
    public const GATEWAY_CONFIG_ERROR = 503;
    public const STRATEGY_NOT_SET = 500;
    public const PAYMENT_FAILED = 402;

    public function __construct(string $message, int $code, array $errors = [])
    {
        parent::__construct($message, $code, $errors);
    }
}
