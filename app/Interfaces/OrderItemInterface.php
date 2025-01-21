<?php

namespace App\Interfaces;

use App\Interfaces\GeneralInterface;

interface OrderItemInterface extends GeneralInterface
{
    public function addItem(Order $order, array $item): Order;
}
