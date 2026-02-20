<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ProcessMockPaymentRequest;
use App\Http\Requests\Api\RefundPaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Ecommerce\PaymentService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function processMock(Order $order, ProcessMockPaymentRequest $request, PaymentService $payments): JsonResponse
    {
        $payment = $payments->createMockPayment($order, $request->validated());

        return response()->json([
            'message' => 'Payment processed.',
            'data' => $payment,
        ], 201);
    }

    public function refund(Payment $payment, RefundPaymentRequest $request, PaymentService $payments): JsonResponse
    {
        $refunded = $payments->refundPayment($payment, request()->user(), $request->validated());

        return response()->json([
            'message' => 'Payment refunded.',
            'data' => $refunded,
        ]);
    }
}
