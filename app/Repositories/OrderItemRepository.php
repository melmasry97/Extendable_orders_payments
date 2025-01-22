<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use App\Enums\OrderStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Interfaces\OrderItemInterface;

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


    public function getItemsByOrder(Order $order): Collection
    {
        return $this->getBy(['order_id' => $order->id], ['product']);
    }

}
