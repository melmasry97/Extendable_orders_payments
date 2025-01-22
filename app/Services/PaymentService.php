<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Interfaces\PaymentGatewayInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Payment\PaymentStrategyManager;
use App\Interfaces\PaymentRepositoryInterface;

class PaymentService
{
    public function __construct(
        protected PaymentRepositoryInterface $paymentRepository,
        protected PaymentStrategyManager $strategyManager
    ) {}

    protected ?PaymentGatewayInterface $strategy = null;

    public function setPaymentStrategy(PaymentGateway $gateway): void
    {
        $gatewayClass = $gateway->class_name;
        if (!class_exists($gatewayClass)) {
            throw new \RuntimeException("Payment gateway class {$gatewayClass} not found");
        }

        $this->strategy = new $gatewayClass($gateway->config);
    }

    public function executePayment(Order $order, array $paymentData): Payment
    {
        if (!$this->strategy) {
            throw new \RuntimeException('Payment strategy not set');
        }

        // Check if order is in confirmed status
        if ($order->status !== 'confirmed') {
            throw new \InvalidArgumentException('Payments can only be processed for confirmed orders');
        }

        // Process payment through selected strategy
        $response = $this->strategy->processPayment($order->total_amount, $paymentData);

        // Create payment record
        return Payment::create([
            'order_id' => $order->id,
            'payment_gateway_id' => $this->getActiveGateway($this->strategy->getName())->id,
            'amount' => $order->total_amount,
            'status' => $response['status'],
            'transaction_id' => $response['transaction_id'],
            'gateway_response' => $response
        ]);
    }

    public function getActiveGateway(string $name): PaymentGateway
    {
        $gateway = PaymentGateway::where('name', $name)
            ->where('is_active', true)
            ->first();

        if (!$gateway) {
            throw new ModelNotFoundException("Active payment gateway '{$name}' not found");
        }

        return $gateway;
    }

    public function validateStrategyConfig(string $gatewayClass, array $config): bool
    {
        if (!class_exists($gatewayClass)) {
            throw new \RuntimeException("Payment gateway class {$gatewayClass} not found");
        }

        /** @var PaymentGatewayInterface */
        $strategy = new $gatewayClass($config);
        return $strategy->validateConfig($config);
    }

    public function processPayment(Order $order, string $gatewayName, array $paymentData): Payment
    {
        // Validate order status
        if ($order->status !== 'confirmed') {
            throw new \InvalidArgumentException('Payments can only be processed for confirmed orders');
        }

        // Get and set payment gateway
        $gateway = $this->paymentRepository->getActiveGateway($gatewayName);
        $this->strategyManager->setStrategy($gateway);

        // Process payment through strategy
        $response = $this->strategyManager->processPayment($order->total_amount, $paymentData);

        // Create payment record
        return $this->paymentRepository->createPayment($order, $gateway, $response);
    }

    public function getOrderPayments(Order $order): mixed
    {
        return $this->paymentRepository->getOrderPayments($order, ['gateway']);
    }

    public function getAllPayments(): mixed
    {
        return $this->paymentRepository->getAllPaginated(['order', 'gateway']);
    }

    public function findPayment(int $id): ?Payment
    {
        return $this->paymentRepository->findById($id, ['order', 'gateway']);
    }
}
