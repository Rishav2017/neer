<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class PaymentController extends Controller
{
    use ApiResponse;

    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Create a payment order for an existing order
     * POST /payments/create-order
     */
    public function createOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|uuid|exists:orders,id',
            'payment_method' => 'required|in:online,cod',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $order = Order::where('id', $request->order_id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$order) {
            return $this->error('Order not found', 404);
        }

        // Check if order already has a successful payment
        if ($order->isPaid()) {
            return $this->error('Order is already paid', 400);
        }

        // Check if order is in valid state for payment
        if ($order->isTerminal()) {
            return $this->error('Cannot process payment for a completed or cancelled order', 400);
        }

        try {
            $paymentData = $this->paymentService->createPaymentOrder(
                $order,
                $request->payment_method
            );

            return $this->success($paymentData, 'Payment order created successfully');
        } catch (Exception $e) {
            return $this->error('Failed to create payment order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Verify payment after client-side completion
     * POST /payments/verify
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $payment = $this->paymentService->getPaymentByGatewayOrderId($request->razorpay_order_id);

        if (!$payment) {
            return $this->error('Payment not found', 404);
        }

        // Verify ownership
        if ($payment->user_id !== $request->user()->id) {
            return $this->error('Unauthorized', 403);
        }

        if ($payment->isPaid()) {
            return $this->success([
                'payment_id' => $payment->id,
                'status' => $payment->status,
            ], 'Payment already verified');
        }

        $verified = $this->paymentService->verifyAndCompletePayment($payment, $request->all());

        if ($verified) {
            return $this->success([
                'payment_id' => $payment->id,
                'status' => 'paid',
                'order_id' => $payment->order_id,
            ], 'Payment verified successfully');
        }

        return $this->error('Payment verification failed', 400);
    }

    /**
     * Get payment status for an order
     * GET /payments/{orderId}/status
     */
    public function status(string $orderId, Request $request): JsonResponse
    {
        $order = Order::where('id', $orderId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$order) {
            return $this->error('Order not found', 404);
        }

        $paymentStatus = $this->paymentService->getPaymentStatus($order);

        return $this->success($paymentStatus, 'Payment status retrieved');
    }

    /**
     * Get user's payment history
     * GET /payments/history
     */
    public function history(Request $request): JsonResponse
    {
        $payments = Payment::where('user_id', $request->user()->id)
            ->with('order:id,status,total_amount')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->success($payments, 'Payment history retrieved');
    }
}
