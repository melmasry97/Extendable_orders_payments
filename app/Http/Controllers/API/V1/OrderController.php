<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Order;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\OrderIndexRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Interfaces\OrderInterface;
class OrderController extends Controller
{
    public function __construct(
        private OrderInterface $orderInterface
    ) {
    }

    /**
     * Display a listing of orders.
     */
    public function index(OrderIndexRequest $request): JsonResponse
    {
        return ResponseHelper::success(
            $this->orderInterface->getPaginated(['user']),
            'Orders fetched successfully'
        );
    }

    /**
     * Store a newly created order.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderInterface->create($request->validated());
        return ResponseHelper::success($order, 'Order created successfully', 201);
    }

    /**
     * Display the specified order.
     */
    public function show(int $id): JsonResponse
    {
        $order = $this->orderInterface->getOrderWithDetails($id);
        return ResponseHelper::success($order, 'Order fetched successfully');

    }

    /**
     * Update the specified order.
     */
    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {

        return $this->orderInterface->update($order->id, $request->validated()) ?
            ResponseHelper::success($order->fresh(), 'Order updated successfully') :
            ResponseHelper::error('Cannot update order with payments');

    }

    /**
     * Remove the specified order.
     */
    public function destroy(Order $order): JsonResponse
    {

        return $this->orderInterface->destroy($order->id) ?
            ResponseHelper::success(null, 'Order deleted successfully') :
            ResponseHelper::error('Cannot delete order with payments');

    }
}
