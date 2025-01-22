<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Interfaces\PaymentRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function getAllPaginated(array $relations = []): LengthAwarePaginator
    {
        return Payment::with($relations)->paginate();
    }

    public function getOrderPayments(Order $order, array $relations = []): LengthAwarePaginator
    {
        return $order->payments()->with($relations)->paginate();
    }

    public function findById(int $id, array $relations = []): ?Payment
    {
        return Payment::with($relations)->find($id);
    }

    public function getActiveGateway(string $name): PaymentGateway
    {
        $gateway = PaymentGateway::where('name', $name)
            ->where('is_active', true) //todo:: active scope
            ->first();

        if (!$gateway) {
            throw new ModelNotFoundException("Active payment gateway '{$name}' not found");
        }

        return $gateway;
    }

    public function createPayment(Order $order, PaymentGateway $gateway, array $paymentResponse): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'payment_gateway_id' => $gateway->id,
            'amount' => $order->total_amount,
            'status' => $paymentResponse['status'],
            'transaction_id' => $paymentResponse['transaction_id'],
            'gateway_response' => $paymentResponse
        ]);
    }
}
