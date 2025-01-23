<?php

namespace App\Services\Payment;

use App\Models\PaymentGateway;
use App\Exceptions\PaymentException;
use App\Interfaces\PaymentGatewayInterface;

class PaymentStrategyManager
{
    protected ?PaymentGatewayInterface $strategy = null;

    public function setStrategy(PaymentGateway $gateway): void
    {
        $gatewayClass = $gateway->class_name;
        if (!class_exists($gatewayClass)) {
            throw new \RuntimeException("Payment gateway class {$gatewayClass} not found");
        }

        $this->strategy = new $gatewayClass($gateway->config);
    }

    public function getStrategy(): PaymentGatewayInterface
    {
        if (!$this->strategy) {
            throw new \RuntimeException('Payment strategy not set');
        }

        return $this->strategy;
    }

    public function processPayment(float $amount, array $paymentData): array
    {
        return $this->getStrategy()->processPayment($amount, $paymentData);
    }

}
