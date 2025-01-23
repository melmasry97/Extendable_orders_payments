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
use App\Http\Resources\OrderItemResource;

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
        return ResponseHelper::success(
            OrderItemResource::collection($this->orderItemInterface->getItemsByOrder($order))
        );
    }

    /**
     * Add items to an order
     */
    public function store(AddItemRequest $request, Order $order): JsonResponse
    {
        $this->orderItemInterface->add($order->id, $request->validated()['items']);
        return ResponseHelper::success(
            OrderItemResource::collection($this->orderItemInterface->getItemsByOrder($order)),
            'Items added successfully',
            201
        );
    }

    public function show(Order $order, OrderItem $item): JsonResponse
    {
        return ResponseHelper::success(
            new OrderItemResource($item)
        );
    }

    /**
     * Update an order item
     */
    public function update(UpdateItemRequest $request, Order $order, OrderItem $item): JsonResponse
    {
        $this->orderItemInterface->update($item->id, $request->validated());
        return ResponseHelper::success(
            new OrderItemResource($item->fresh()),
            'Item updated successfully'
        );
    }

    /**
     * Remove an item from order
     */
    public function destroy(Order $order, OrderItem $item): JsonResponse
    {
        $this->orderItemInterface->destroy($item->id);
        return ResponseHelper::success(null, 'Item removed successfully');
    }
}
