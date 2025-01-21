<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Http\Requests\OrderItem\AddItemRequest;
use App\Http\Requests\OrderItem\UpdateItemRequest;
use App\Http\Resources\OrderItemResource;

class OrderItemController extends Controller
{
    /**
     * Add items to an order
     */
    public function store(AddItemRequest $request, Order $order)
    {
        $items = $request->validated();

        $orderItems = $order->items()->createMany($items['items']);

        return OrderItemResource::collection($orderItems)
            ->additional(['message' => 'Items added successfully']);
    }

    /**
     * Update an order item
     */
    public function update(UpdateItemRequest $request, Order $order, OrderItem $item)
    {
        $this->authorize('update', [$item, $order]);

        $item->update($request->validated());

        return new OrderItemResource($item);
    }

    /**
     * Remove an item from order
     */
    public function destroy(Order $order, OrderItem $item)
    {
        $this->authorize('delete', [$item, $order]);

        $item->delete();

        return response()->noContent();
    }
}
