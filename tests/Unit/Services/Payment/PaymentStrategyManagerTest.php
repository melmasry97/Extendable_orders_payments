<?php

namespace Tests\Unit\Services\Payment;

use Tests\TestCase;
use RuntimeException;
use App\Models\PaymentGateway;
use App\Exceptions\PaymentException;
use App\Interfaces\PaymentGatewayInterface;
use App\Services\Payment\StripePaymentGateway;
use App\Services\Payment\PaymentStrategyManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentStrategyManagerTest extends TestCase
{
    use RefreshDatabase;

    private PaymentStrategyManager $strategyManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategyManager = new PaymentStrategyManager();
    }

    public function test_set_strategy_creates_gateway_instance()
    {
        $gateway = PaymentGateway::factory()->create([
            'name' => 'stripe',
            'class_name' => StripePaymentGateway::class,
            'config' => [
                'secret_key' => 'test_key',
                'public_key' => 'test_pub_key'
            ],
            'is_active' => true
        ]);

        $this->strategyManager->setStrategy($gateway);

        $this->assertInstanceOf(PaymentGatewayInterface::class, $this->strategyManager->getStrategy());
    }

    public function test_set_strategy_throws_exception_for_invalid_gateway_class()
    {
        $gateway = PaymentGateway::factory()->create([
            'class_name' => 'NonExistentGateway'
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Payment gateway class NonExistentGateway not found');

        $this->strategyManager->setStrategy($gateway);
    }

    public function test_process_payment_throws_exception_when_strategy_not_set()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Payment strategy not set');

        $this->strategyManager->processPayment(100.00, []);
    }

    public function test_process_payment_delegates_to_strategy()
    {
        $gateway = PaymentGateway::factory()->create([
            'name' => 'stripe',
            'class_name' => StripePaymentGateway::class,
            'config' => [
                'secret_key' => 'test_key',
                'public_key' => 'test_pub_key'
            ],
            'is_active' => true
        ]);

        $this->strategyManager->setStrategy($gateway);

        $amount = 100.00;
        $paymentData = [
            'payment_method' => 'card',
            'card_number' => '4242424242424242'
        ];

        $response = $this->strategyManager->processPayment($amount, $paymentData);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('transaction_id', $response);
    }
}
