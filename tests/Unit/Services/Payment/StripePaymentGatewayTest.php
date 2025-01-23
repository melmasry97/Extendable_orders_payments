<?php

namespace Tests\Unit\Services\Payment;

use Tests\TestCase;
use App\Exceptions\PaymentException;
use App\Services\Payment\StripePaymentGateway;

class StripePaymentGatewayTest extends TestCase
{
    private StripePaymentGateway $gateway;
    private array $validConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validConfig = [
            'secret_key' => 'test_key',
            'public_key' => 'test_pub_key'
        ];

        $this->gateway = new StripePaymentGateway($this->validConfig);
    }

    public function test_validate_config_returns_true_for_valid_config()
    {
        $result = $this->gateway->validateConfig($this->validConfig);

        $this->assertTrue($result);
    }

    public function test_validate_config_returns_false_for_invalid_config()
    {
        $invalidConfig = ['secret_key' => 'test_key'];

        $result = $this->gateway->validateConfig($invalidConfig);

        $this->assertFalse($result);
    }

    public function test_process_payment_returns_valid_response()
    {
        $amount = 100.00;
        $paymentData = [
            'payment_method' => 'stripe',
            'card_number' => '4242424242424242',
            'expiry_month' => '12',
            'expiry_year' => '2025',
            'cvv' => '123'
        ];

        $response = $this->gateway->processPayment($amount, $paymentData);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('transaction_id', $response);
        $this->assertEquals('successful', $response['status']);
        $this->assertNotEmpty($response['transaction_id']);
    }

    public function test_get_name_returns_gateway_name()
    {
        $this->assertEquals('stripe', $this->gateway->getName());
    }
}
