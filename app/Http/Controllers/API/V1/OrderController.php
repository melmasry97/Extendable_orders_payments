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
use App\Http\Resources\OrderResource;

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
        $query = Order::query()->filterStatus($request->only('status'))->with('user');
        return ResponseHelper::success(
            OrderResource::collection($query->paginate($request->get('per_page', 15))),
            'Orders fetched successfully'
        );
    }

    /**
     * Store a newly created order.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        return ResponseHelper::success(
            new OrderResource($this->orderInterface->create($request->validated())),
            'Order created successfully',
            201
        );
    }

    /**
     * Display the specified order.
     */
    public function show(int $id): JsonResponse
    {
        return ResponseHelper::success(
            new OrderResource($this->orderInterface->getOrderWithDetails($id)),
            'Order fetched successfully'
        );
    }

    /**
     * Update the specified order.
     */
    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        $this->orderInterface->update($order->id, $request->validated());
        return ResponseHelper::success(
            new OrderResource($order->fresh()),
            'Order updated successfully'
        );
    }

    /**
     * Remove the specified order.
     */
    public function destroy(Order $order): JsonResponse
    {
        return ResponseHelper::success(
            $this->orderInterface->destroy($order->id),
            'Order deleted successfully'
        );
    }
}
