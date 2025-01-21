<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Http\Requests\Order\AddOrderItemsRequest;
use App\Models\Order;
use App\Models\Product;
use App\Interfaces\OrderInterface;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(
        private OrderInterface $orderRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderRepository->getAllPaginated(
            perPage: $request->input('per_page', 15),
            status: $request->input('status')
        );

        return ResponseHelper::success($orders);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderRepository->createOrder($request->validated());
            return ResponseHelper::success($order, 'Order created successfully', 201);
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to create order: ' . $e->getMessage(), 500);
        }
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['user', 'payments']);
        return ResponseHelper::success($order);
    }

    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        try {
            $order = $this->orderRepository->updateOrder($order, $request->validated());
            return ResponseHelper::success($order, 'Order updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 400);
        }
    }

    public function destroy(Order $order): JsonResponse
    {
        try {
            $this->orderRepository->deleteOrder($order);
            return ResponseHelper::success(null, 'Order deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 400);
        }
    }

    public function addItems(Order $order, AddOrderItemsRequest $request): JsonResponse
    {
        try {
            DB::transaction(function () use ($order, $request) {
                $totalAmount = 0;

                foreach ($request->items as $item) {
                    $product = Product::findOrFail($item['product_id']);

                    $orderItem = $order->items()->create([
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $product->price,
                        'subtotal' => $product->price * $item['quantity']
                    ]);

                    $totalAmount += $orderItem->subtotal;
                }

                $order->update([
                    'total_amount' => $order->total_amount + $totalAmount
                ]);
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Items added to order successfully',
                'data' => $order->fresh(['items.product'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add items to order: ' . $e->getMessage()
            ], 400);
        }
    }

    public function updateItems(Order $order, UpdateOrderRequest $request): JsonResponse
    {
        $order = $this->orderRepository->updateItems($order, $request->validated()['items']);

        return response()->json([
            'status' => 'success',
            'message' => 'Items updated successfully',
            'data' => $order
        ]);
    }

    public function removeItems(Order $order, array $itemIds): JsonResponse
    {
        $this->orderRepository->removeItems($order, $itemIds);

        return response()->json([
            'status' => 'success',
            'message' => 'Items removed successfully'
        ]);
    }
}
