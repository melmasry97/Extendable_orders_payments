<?php

namespace Tests\Unit\Services;

use Exception;
use Throwable;
use Tests\TestCase;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\PaymentService;
use App\Exceptions\PaymentException;
use App\Services\Payment\StripePaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $paymentService;
    private Order $order;
    private PaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentService = app(PaymentService::class);

        // Create a confirmed order
        $this->order = Order::factory()
            ->confirmed()
            ->create(['total_amount' => 100.00]);

        // Create an active payment gateway
        $this->gateway = PaymentGateway::factory()
            ->stripe()
            ->create();
    }

    public function test_process_payment_with_valid_data(): void
    {
        $paymentData = [
            'payment_method' => 'stripe',
            'card_number' => '4242424242424242',
            'expiry_month' => '12',
            'expiry_year' => '2025',
            'cvv' => '123'
        ];

        $payment = $this->paymentService->processPayment(
            $this->order,
            $this->gateway->name,
            $paymentData
        );

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals($this->order->id, $payment->order_id);
        $this->assertEquals($this->gateway->id, $payment->payment_gateway_id);
        $this->assertEquals($this->order->total_amount, $payment->amount);
        $this->assertNotNull($payment->transaction_id);
    }

    public function test_throws_exception_when_processing_payment_for_non_confirmed_order(): void
    {
        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('Payments can only be processed for confirmed orders');
        $this->expectExceptionCode(PaymentException::ORDER_NOT_CONFIRMED);

        $pendingOrder = Order::factory()->pending()->create();

        $this->paymentService->processPayment(
            $pendingOrder,
            $this->gateway->name,
            ['payment_method' => 'card']
        );
    }

    public function test_throws_exception_when_payment_gateway_not_found(): void
    {
        $this->expectException(PaymentException::class);
        $this->expectExceptionCode(PaymentException::PAYMENT_FAILED);

        $this->paymentService->processPayment(
            $this->order,
            'invalid_gateway',
            ['payment_method' => 'card']
        );
    }

    // public function test_throws_exception_when_gateway_config_invalid(): void
    // {
    //     $this->expectException(PaymentException::class);
    //     $this->expectExceptionMessage('Payment processing failed: Invalid gateway configuration');
    //     $this->expectExceptionCode(PaymentException::PAYMENT_FAILED);

    //     $invalidGateway = PaymentGateway::factory()->create([
    //         'config' => [], // Empty config
    //         'class_name' => StripePaymentGateway::class
    //     ]);

    //     $this->paymentService->processPayment(
    //         $this->order,
    //         $invalidGateway->name,
    //         [
    //             'payment_method' => 'stripe',
    //             'card_number' => '4242424242424242'
    //         ]
    //     );
    // }

    public function test_can_get_order_payments(): void
    {
        Payment::factory()->count(3)->create([
            'order_id' => $this->order->id
        ]);

        $payments = $this->paymentService->getOrderPayments($this->order);

        $this->assertCount(3, $payments);
        $this->assertInstanceOf(Payment::class, $payments->first());
    }

    public function test_can_find_payment_by_id(): void
    {
        $createdPayment = Payment::factory()->create();

        $payment = $this->paymentService->findPayment($createdPayment->id);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals($createdPayment->id, $payment->id);
    }

    public function test_validate_gateway_config(): void
    {
        $config = [
            'secret_key' => 'test_key',
            'public_key' => 'test_pub_key'
        ];

        $isValid = $this->paymentService->validateStrategyConfig(
            StripePaymentGateway::class,
            $config
        );

        $this->assertTrue($isValid);
    }
}
