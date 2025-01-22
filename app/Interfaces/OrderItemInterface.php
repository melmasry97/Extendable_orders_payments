<?php

namespace App\Interfaces;

use App\Interfaces\GeneralInterface;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;

interface OrderItemInterface extends GeneralInterface
{
    public function add(int $orderId, array $items): Collection;
    public function getItemsByOrder(Order $order): Collection;
}
