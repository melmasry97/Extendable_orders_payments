<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use App\Enums\OrderStatus;
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

    public function add(int $orderId, array $items): Collection
    {
        return DB::transaction(function () use ($orderId, $items) {
            return collect($items)->map(function ($item) use ($orderId) {
                $product = Product::find($item['product_id']);
                return $this->create([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price
                ]);
            });
        });
    }

    public function update(int $id, array $input): bool
    {
        $item = $this->model->find($id);
        if ($item->order->status === OrderStatus::CANCELLED->value ||
            ($item->order->payments()->exists() &&
            $item->order->status === OrderStatus::CONFIRMED->value)) {
            return false;
        }
        return $item->update($input);
    }

    public function getItemsByOrder(Order $order): Collection
    {
        return $this->getBy(['order_id' => $order->id], ['product']);
    }

}
