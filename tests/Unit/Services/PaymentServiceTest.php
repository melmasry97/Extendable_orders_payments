<?php

namespace Tests\Unit\Services;

use Mockery;
use Tests\TestCase;
use App\Models\Order;
use App\Models\Payment;
use App\Enums\OrderStatus;
use Mockery\MockInterface;
use App\Models\PaymentGateway;
use App\Services\PaymentService;
use App\Exceptions\PaymentException;
use App\Interfaces\PaymentGatewayInterface;
use App\Interfaces\PaymentRepositoryInterface;
use App\Services\Payment\StripePaymentGateway;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\Payment\PaymentStrategyManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $paymentService;
    private Order $order;
    private PaymentGateway $gateway;
    private MockInterface $paymentRepository;
    private MockInterface $strategyManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
        $this->strategyManager = Mockery::mock(PaymentStrategyManager::class);
        $this->paymentService = new PaymentService(
            $this->paymentRepository,
            $this->strategyManager
        );

        // Create a confirmed order
        $this->order = Order::factory()
            ->confirmed()
            ->create(['total_amount' => 100.00]);

        // Create an active payment gateway
        $this->gateway = PaymentGateway::factory()
            ->stripe()
            ->create();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
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

        $expectedResponse = [
            'status' => 'success',
            'transaction_id' => 'test_tx_id'
        ];

        $this->paymentRepository
            ->expects('getActiveGateway')
            ->with($this->gateway->name)
            ->andReturn($this->gateway);

        $this->strategyManager
            ->expects('setStrategy')
            ->with($this->gateway)
            ->andReturnSelf();

        $this->strategyManager
            ->expects('processPayment')
            ->with($this->order->total_amount, $paymentData)
            ->andReturn($expectedResponse);

        $this->paymentRepository
            ->expects('createPayment')
            ->with($this->order, $this->gateway, $expectedResponse)
            ->andReturn(new Payment([
                'order_id' => $this->order->id,
                'payment_gateway_id' => $this->gateway->id,
                'amount' => $this->order->total_amount,
                'transaction_id' => $expectedResponse['transaction_id']
            ]));

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
        $payments = Payment::factory()->count(3)->create([
            'order_id' => $this->order->id
        ]);

        $expectedPayments = new LengthAwarePaginator(
            $payments,
            3,
            15,
            1
        );

        $this->paymentRepository
            ->expects('getOrderPayments')
            ->with($this->order, ['gateway'])
            ->andReturn($expectedPayments);

        $result = $this->paymentService->getOrderPayments($this->order);

        $this->assertEquals($expectedPayments, $result);
        $this->assertCount(3, $result->items());
        $this->assertInstanceOf(Payment::class, $result->items()[0]);
    }

    public function test_can_find_payment_by_id(): void
    {
        $createdPayment = Payment::factory()->create();

        $this->paymentRepository
            ->expects('findById')
            ->with($createdPayment->id, ['order', 'gateway'])
            ->andReturn($createdPayment);

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


    public function test_validate_strategy_config_throws_exception_for_nonexistent_class()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Payment gateway class NonExistentGateway not found');

        $this->paymentService->validateStrategyConfig('NonExistentGateway', []);
    }

    public function test_get_order_payments_returns_payments_with_relations()
    {
        $order = Order::factory()->create();
        $payments = Payment::factory()->count(2)->create(['order_id' => $order->id]);
        $expectedPayments = new LengthAwarePaginator(
            $payments,
            2,
            15,
            1
        );

        $this->paymentRepository
            ->expects('getOrderPayments')
            ->with($order, ['gateway'])
            ->andReturn($expectedPayments);

        $result = $this->paymentService->getOrderPayments($order);

        $this->assertEquals($expectedPayments, $result);
    }

    public function test_get_all_payments_returns_paginated_payments()
    {
        $expectedPayments = new LengthAwarePaginator(
            Payment::factory()->count(2)->create(),
            2,
            15,
            1
        );

        $this->paymentRepository
            ->expects('getAllPaginated')
            ->with(['order', 'gateway'])
            ->andReturn($expectedPayments);

        $result = $this->paymentService->getAllPayments();

        $this->assertEquals($expectedPayments, $result);
    }

    public function test_find_payment_returns_payment_with_relations()
    {
        $payment = Payment::factory()->create();

        $this->paymentRepository
            ->expects('findById')
            ->with($payment->id, ['order', 'gateway'])
            ->andReturn($payment);

        $result = $this->paymentService->findPayment($payment->id);

        $this->assertEquals($payment, $result);
    }

    public function test_find_payment_returns_null_for_nonexistent_payment()
    {
        $this->paymentRepository
            ->expects('findById')
            ->with(999, ['order', 'gateway'])
            ->andReturn(null);

        $result = $this->paymentService->findPayment(999);

        $this->assertNull($result);
    }

    public function test_process_payment_throws_exception_for_non_confirmed_order()
    {
        $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('Payments can only be processed for confirmed orders');
        $this->expectExceptionCode(PaymentException::ORDER_NOT_CONFIRMED);

        $this->paymentService->processPayment($order, 'stripe', []);
    }

    public function test_process_payment_throws_exception_when_gateway_not_found()
    {
        $order = Order::factory()->create(['status' => OrderStatus::CONFIRMED]);

        $this->paymentRepository
            ->expects('getActiveGateway')
            ->with('nonexistent_gateway')
            ->andThrow(new ModelNotFoundException);

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage("Active payment gateway 'nonexistent_gateway' not found");
        $this->expectExceptionCode(PaymentException::GATEWAY_NOT_FOUND);

        $this->paymentService->processPayment($order, 'nonexistent_gateway', []);
    }
}

