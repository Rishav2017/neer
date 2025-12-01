<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class RazorpayGateway implements PaymentGatewayInterface
{
    protected string $keyId;
    protected string $keySecret;
    protected string $webhookSecret;
    protected string $baseUrl = 'https://api.razorpay.com/v1';

    public function __construct()
    {
        $this->keyId = config('services.razorpay.key_id');
        $this->keySecret = config('services.razorpay.key_secret');
        $this->webhookSecret = config('services.razorpay.webhook_secret');
    }

    /**
     * Create a Razorpay order
     */
    public function createOrder(Order $order, array $options = []): array
    {
        $amount = (int) ($order->total_amount * 100); // Razorpay expects amount in paise
        $currency = $options['currency'] ?? 'INR';

        $response = Http::withBasicAuth($this->keyId, $this->keySecret)
            ->post("{$this->baseUrl}/orders", [
                'amount' => $amount,
                'currency' => $currency,
                'receipt' => $order->id,
                'notes' => [
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                ],
            ]);

        if (!$response->successful()) {
            Log::error('Razorpay order creation failed', [
                'order_id' => $order->id,
                'response' => $response->json(),
            ]);
            throw new Exception('Failed to create Razorpay order: ' . ($response->json()['error']['description'] ?? 'Unknown error'));
        }

        $data = $response->json();

        return [
            'gateway_order_id' => $data['id'],
            'amount' => $amount,
            'amount_display' => $order->total_amount,
            'currency' => $currency,
            'receipt' => $data['receipt'],
            'status' => $data['status'],
            'key_id' => $this->keyId,
        ];
    }

    /**
     * Verify payment signature
     */
    public function verifyPayment(Payment $payment, array $payload): bool
    {
        $orderId = $payload['razorpay_order_id'] ?? '';
        $paymentId = $payload['razorpay_payment_id'] ?? '';
        $signature = $payload['razorpay_signature'] ?? '';

        $expectedSignature = hash_hmac(
            'sha256',
            $orderId . '|' . $paymentId,
            $this->keySecret
        );

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Process webhook payload
     */
    public function processWebhook(array $payload): array
    {
        $event = $payload['event'] ?? '';
        $paymentEntity = $payload['payload']['payment']['entity'] ?? [];

        return [
            'event' => $event,
            'payment_id' => $paymentEntity['id'] ?? null,
            'order_id' => $paymentEntity['order_id'] ?? null,
            'amount' => isset($paymentEntity['amount']) ? $paymentEntity['amount'] / 100 : null,
            'currency' => $paymentEntity['currency'] ?? 'INR',
            'status' => $paymentEntity['status'] ?? null,
            'method' => $paymentEntity['method'] ?? null,
            'error_code' => $paymentEntity['error_code'] ?? null,
            'error_description' => $paymentEntity['error_description'] ?? null,
            'raw' => $payload,
        ];
    }

    /**
     * Refund a payment
     */
    public function refund(Payment $payment, ?float $amount = null): array
    {
        $refundAmount = $amount ? (int) ($amount * 100) : (int) ($payment->amount * 100);

        $response = Http::withBasicAuth($this->keyId, $this->keySecret)
            ->post("{$this->baseUrl}/payments/{$payment->gateway_payment_id}/refund", [
                'amount' => $refundAmount,
                'notes' => [
                    'order_id' => $payment->order_id,
                    'reason' => 'Customer requested refund',
                ],
            ]);

        if (!$response->successful()) {
            Log::error('Razorpay refund failed', [
                'payment_id' => $payment->id,
                'response' => $response->json(),
            ]);
            throw new Exception('Failed to process refund: ' . ($response->json()['error']['description'] ?? 'Unknown error'));
        }

        return $response->json();
    }

    /**
     * Get payment status from Razorpay
     */
    public function getPaymentStatus(string $gatewayPaymentId): array
    {
        $response = Http::withBasicAuth($this->keyId, $this->keySecret)
            ->get("{$this->baseUrl}/payments/{$gatewayPaymentId}");

        if (!$response->successful()) {
            throw new Exception('Failed to fetch payment status');
        }

        return $response->json();
    }

    /**
     * Fetch order details from Razorpay
     */
    public function getOrderStatus(string $gatewayOrderId): array
    {
        $response = Http::withBasicAuth($this->keyId, $this->keySecret)
            ->get("{$this->baseUrl}/orders/{$gatewayOrderId}");

        if (!$response->successful()) {
            throw new Exception('Failed to fetch order status');
        }

        return $response->json();
    }
}
