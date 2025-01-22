<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Http\Controllers\Controller;
use App\Helpers\ResponseHelper;
use App\Http\Requests\Payment\ProcessPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Traits\PaginatedResponse;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    use PaginatedResponse;

    public function __construct(
        protected PaymentService $paymentService
    ) {
    }

    public function index(): JsonResponse
    {
        $payments = $this->paymentService->getAllPayments();
        return $this->respondWithPagination(
            $payments,
            PaymentResource::collection($payments),
            'payments',
            'Payments retrieved successfully'
        );
    }

    public function show(Payment $payment): JsonResponse
    {
        $payment = $this->paymentService->findPayment($payment->id);
        return ResponseHelper::success(new PaymentResource($payment),'Payment details retrieved successfully');
    }

    public function process(ProcessPaymentRequest $request, Order $order): JsonResponse
    {
        $payment = $this->paymentService->processPayment(
            $order,
            $request->gateway,
            $request->validated()
        );

        return ResponseHelper::success(new PaymentResource($payment),'Payment processed successfully');
    }

    public function getOrderPayments(Order $order): JsonResponse
    {
        $payments = $this->paymentService->getOrderPayments($order);
        return $this->respondWithPagination(
            $payments,
            PaymentResource::collection($payments),
            'payments',
            'Order payments retrieved successfully'
        );
    }
}
