<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Http\Requests\OrderItem\AddItemRequest;
use App\Http\Requests\OrderItem\UpdateItemRequest;
use App\Interfaces\OrderItemInterface;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class OrderItemController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private OrderItemInterface $orderItemInterface
    ) {
    }

    /**
     * Get all items for an order
     */
    public function index(Order $order): JsonResponse
    {
        return ResponseHelper::success($this->orderItemInterface->getItemsByOrder($order));
    }

    /**
     * Add items to an order
     */
    public function store(AddItemRequest $request, Order $order): JsonResponse
    {
        return $this->orderItemInterface->add($order->id, $request->validated()['items']) ?
            ResponseHelper::success($this->orderItemInterface->getItemsByOrder($order), 'Items added successfully', 201) :
            ResponseHelper::error('Failed to add items', 400);

    }

    public function show(Order $order, OrderItem $item): JsonResponse
    {
        return ResponseHelper::success($item);
    }

    /**
     * Update an order item
     */
    public function update(UpdateItemRequest $request, Order $order, OrderItem $item): JsonResponse
    {
        return $this->orderItemInterface->update($item->id, $request->validated()) ?
            ResponseHelper::success($item->fresh(), 'Item updated successfully') :
            ResponseHelper::error('Failed to update item', 400);
    }

    /**
     * Remove an item from order
     */
    public function destroy(Order $order, OrderItem $item): JsonResponse
    {
        return $this->orderItemInterface->destroy($item->id) ?
            ResponseHelper::success(null, 'Item removed successfully') :
            ResponseHelper::error('Failed to remove item', 400);
    }
}
