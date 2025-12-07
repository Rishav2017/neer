<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Exception;

class PaymentCheckoutController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Show Razorpay checkout page
     * GET /payment/checkout/{orderId}
     */
    public function checkout(string $orderId, Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return view('payment.error', ['message' => 'Authentication token is required']);
        }

        // Verify token and get user
        $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token)?->tokenable;

        if (!$user) {
            return view('payment.error', ['message' => 'Invalid or expired token']);
        }

        $order = Order::where('id', $orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return view('payment.error', ['message' => 'Order not found']);
        }

        if ($order->isPaid()) {
            return view('payment.error', ['message' => 'Order is already paid']);
        }

        try {
            // Create or get existing payment order
            $paymentData = $this->paymentService->createPaymentOrder($order, 'online');

            return view('payment.checkout', [
                'order' => $order,
                'payment' => $paymentData['payment'],
                'razorpay_key' => $paymentData['razorpay_key'],
                'razorpay_order_id' => $paymentData['razorpay_order_id'],
                'amount' => $paymentData['amount'],
                'currency' => $paymentData['currency'],
                'user' => $user,
            ]);
        } catch (Exception $e) {
            return view('payment.error', ['message' => 'Failed to create payment: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle payment success callback
     * POST /payment/success
     */
    public function success(Request $request)
    {
        $request->validate([
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $payment = $this->paymentService->getPaymentByGatewayOrderId($request->razorpay_order_id);

        if (!$payment) {
            return view('payment.result', [
                'success' => false,
                'message' => 'Payment not found',
                'order_id' => null,
            ]);
        }

        $verified = $this->paymentService->verifyAndCompletePayment($payment, $request->all());

        return view('payment.result', [
            'success' => $verified,
            'message' => $verified ? 'Payment successful!' : 'Payment verification failed',
            'order_id' => $payment->order_id,
        ]);
    }

    /**
     * Handle payment failure/cancel
     * GET /payment/cancel
     */
    public function cancel(Request $request)
    {
        return view('payment.result', [
            'success' => false,
            'message' => 'Payment was cancelled',
            'order_id' => $request->query('order_id'),
        ]);
    }
}
