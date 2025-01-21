<?php

namespace App\Interfaces;

use App\Interfaces\GeneralInterface;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;

interface OrderItemInterface extends GeneralInterface
{
    public function addItems(Order $order, array $items): Collection;
    public function updateItem(OrderItem $item, array $data): OrderItem;
    public function deleteItem(OrderItem $item): bool;
    public function recalculateOrderTotal(Order $order): void;
    public function getByOrder(Order $order): Collection;
}
