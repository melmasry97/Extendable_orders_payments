<?php

namespace App\Interfaces;

interface PaymentGatewayInterface
{
    /**
     * Process a payment
     *
     * @param float $amount
     * @param array $paymentData
     * @return array
     */
    public function processPayment(float $amount, array $paymentData): array;

    /**
     * Validate gateway configuration
     *
     * @param array $config
     * @return bool
     */
    public function validateConfig(array $config): bool;

    /**
     * Get gateway name
     *
     * @return string
     */
    public function getName(): string;
}
