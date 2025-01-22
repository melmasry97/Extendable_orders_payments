<?php

namespace App\Services\Payment;

use App\Interfaces\PaymentGatewayInterface;

abstract class AbstractPaymentGateway implements PaymentGatewayInterface
{
    protected array $config;
    protected string $name;

    public function __construct(array $config)
    {
        if (!$this->validateConfig($config)) {
            throw new \InvalidArgumentException('Invalid gateway configuration');
        }
        $this->config = $config;
    }

    public function getName(): string
    {
        return $this->name;
    }

    abstract public function processPayment(float $amount, array $paymentData): array;

    abstract public function validateConfig(array $config): bool;

    protected function formatAmount(float $amount): int
    {
        // Convert amount to cents/smallest currency unit
        return (int) ($amount * 100);
    }

    protected function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
}
