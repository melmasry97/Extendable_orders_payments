<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Interfaces\OrderItemInterface;
use Illuminate\Database\Eloquent\Model;

class OrderItemRepository extends GeneralRepository implements OrderItemInterface
{
    public function __construct()
    {
        parent::__construct(new OrderItem());
    }

    public function addItems(Order $order, array $items): Collection
    {
        return DB::transaction(function () use ($order, $items) {
            $orderItems = collect();

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);

                $orderItem = $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'] ?? $product->price,
                    'subtotal' => ($item['unit_price'] ?? $product->price) * $item['quantity']
                ]);

                $orderItems->push($orderItem);
            }

            $this->recalculateOrderTotal($order);

            return $orderItems;
        });
    }

    public function updateItem(OrderItem $item, array $data): OrderItem
    {
        return DB::transaction(function () use ($item, $data) {
            $this->setModel($item);
            $this->update([
                'quantity' => $data['quantity'] ?? $item->quantity,
                'unit_price' => $data['unit_price'] ?? $item->unit_price,
                'subtotal' => ($data['unit_price'] ?? $item->unit_price) * ($data['quantity'] ?? $item->quantity)
            ]);

            $this->recalculateOrderTotal($item->order);

            return $item->fresh();
        });
    }

    public function deleteItem(OrderItem $item): bool
    {
        return DB::transaction(function () use ($item) {
            $this->setModel($item);
            $order = $item->order;
            $deleted = $this->delete();

            if ($deleted) {
                $this->recalculateOrderTotal($order);
            }

            return $deleted;
        });
    }

    public function recalculateOrderTotal(Order $order): void
    {
        $total = $order->items()->sum('subtotal');
        $order->update(['total_amount' => $total]);
    }

    public function getByOrder(Order $order): Collection
    {
        return $this->model->where('order_id', $order->id)
            ->with('product')
            ->get();
    }

    public function update(array $input): Model
    {
        return DB::transaction(function () use ($input) {
            $updated = parent::update($input);
            $this->recalculateOrderTotal($updated->order);
            return $updated;
        });
    }

    public function delete(): bool
    {
        return DB::transaction(function () {
            $order = $this->model->order;
            $deleted = parent::delete();

            if ($deleted) {
                $this->recalculateOrderTotal($order);
            }

            return $deleted;
        });
    }
}
