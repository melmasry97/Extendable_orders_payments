<?php

namespace App\Interfaces;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGateway;
use Illuminate\Pagination\LengthAwarePaginator;

interface PaymentRepositoryInterface
{
    public function getAllPaginated(array $relations = []): LengthAwarePaginator;

    public function getOrderPayments(Order $order, array $relations = []): LengthAwarePaginator;

    public function findById(int $id, array $relations = []): ?Payment;

    public function getActiveGateway(string $name): PaymentGateway;

    public function createPayment(Order $order, PaymentGateway $gateway, array $paymentResponse): Payment;
}
