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
    ) {}

    /**
     * Add items to an order
     */
    public function store(AddItemRequest $request, Order $order): JsonResponse
    {
        try {
            $items = $this->orderItemInterface->addItems($order, $request->validated()['items']);
            return ResponseHelper::success($items, 'Items added successfully', 201);
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to add items: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Update an order item
     */
    public function update(UpdateItemRequest $request, Order $order, OrderItem $item): JsonResponse
    {
        try {
            $this->authorize('update', [$item, $order]);

            $updatedItem = $this->orderItemInterface->updateItem($item, $request->validated());
            return ResponseHelper::success($updatedItem, 'Item updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 400);
        }
    }

    /**
     * Remove an item from order
     */
    public function destroy(Order $order, OrderItem $item): JsonResponse
    {
        try {
            $this->authorize('delete', [$item, $order]);

            $this->orderItemInterface->deleteItem($item);
            return ResponseHelper::success(null, 'Item removed successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 400);
        }
    }
}
