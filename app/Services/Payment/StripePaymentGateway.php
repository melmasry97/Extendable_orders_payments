<?php

namespace App\Services\Payment;

class StripePaymentGateway extends AbstractPaymentGateway
{
    protected string $name = 'stripe';

    public function validateConfig(array $config): bool
    {
        return isset($config['secret_key']) &&
               isset($config['public_key']) &&
               !empty($config['secret_key']) &&
               !empty($config['public_key']);
    }

    public function processPayment(float $amount, array $paymentData): array
    {
        // This is where you would integrate with the actual Stripe API
        // For now, we'll simulate the payment process

        $success = rand(0, 10) > 2; // 80% success rate for simulation

        if ($success) {
            return [
                'success' => true,
                'transaction_id' => 'str_' . uniqid(),
                'status' => 'successful',
                'message' => 'Payment processed successfully',
                'amount' => $amount,
                'currency' => 'USD',
                'gateway_response' => [
                    'charge_id' => 'ch_' . uniqid(),
                    'payment_method' => $paymentData['payment_method'] ?? 'card',
                    'paid' => true
                ]
            ];
        }

        return [
            'success' => false,
            'transaction_id' => null,
            'status' => 'failed',
            'message' => 'Payment processing failed',
            'amount' => $amount,
            'currency' => 'USD',
            'gateway_response' => [
                'error' => [
                    'code' => 'payment_failed',
                    'message' => 'The payment could not be processed'
                ]
            ]
        ];
    }
}
