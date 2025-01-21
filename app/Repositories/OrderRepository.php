<?php

namespace App\Repositories;

use App\Models\Order;
use App\Enums\OrderStatus;
use App\Interfaces\OrderInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderRepository extends GeneralRepository implements OrderInterface
{
    public function __construct()
    {
        parent::__construct(new Order());
    }

    public function getAllPaginated(int $perPage = 15, ?string $status = null): LengthAwarePaginator
    {
        return $this->model->with(['user'])
            ->when($status, function (Builder $query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate($perPage);
    }

    public function getById(int $id): ?Order
    {
        return Order::with(['user', 'payments'])->find($id);
    }

    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = $this->create([
                'user_id' => auth()->id(),
                'status' => OrderStatus::PENDING,
                'items' => $data['items'],
                'customer_details' => $data['customer_details'],
                'notes' => $data['notes'] ?? null
            ]);

            return $order->fresh(['user']);
        });
    }

    public function updateOrder(Order $order, array $data): Order
    {
        if (isset($data['status']) && $data['status'] === OrderStatus::CANCELLED->value && $order->payments()->exists()) {
            throw new \Exception('Cannot cancel order with existing payments');
        }

        return DB::transaction(function () use ($order, $data) {
            $order->update([
                'status' => $data['status'] ?? $order->status,
                'items' => $data['items'] ?? $order->items,
                'customer_details' => $data['customer_details'] ?? $order->customer_details,
                'notes' => $data['notes'] ?? $order->notes
            ]);

            if (isset($data['items'])) {
                $order->total_amount = collect($data['items'])->sum(function ($item) {
                    return $item['quantity'] * $item['price'];
                });
                $order->save();
            }

            return $order->fresh(['user']);
        });
    }

    public function deleteOrder(Order $order): bool
    {
        if (!$order->canBeDeleted()) {
            throw new \Exception('Cannot delete order with existing payments');
        }

        return DB::transaction(function () use ($order) {
            return $order->delete();
        });
    }
}
