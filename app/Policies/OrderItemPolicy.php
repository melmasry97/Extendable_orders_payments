<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderItemPolicy
{
    use HandlesAuthorization;

    public function update(User $user, OrderItem $item, Order $order): bool
    {
        return $user->id === $order->user_id && $item->order_id === $order->id;
    }

    public function delete(User $user, OrderItem $item, Order $order): bool
    {
        return $user->id === $order->user_id && $item->order_id === $order->id;
    }
}
