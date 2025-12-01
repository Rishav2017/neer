<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Events\PaymentSuccessful;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PaymentService
{
    protected PaymentGatewayInterface $gateway;
    protected string $defaultGateway;

    public function __construct(?PaymentGatewayInterface $gateway = null)
    {
        $this->defaultGateway = config('services.payment.default_gateway', 'razorpay');
        $this->gateway = $gateway ?? $this->resolveGateway($this->defaultGateway);
    }

    /**
     * Resolve gateway instance by name
     */
    protected function resolveGateway(string $gateway): PaymentGatewayInterface
    {
        return match ($gateway) {
            'razorpay' => new RazorpayGateway(),
            // 'stripe' => new StripeGateway(),
            default => throw new Exception("Unsupported payment gateway: {$gateway}"),
        };
    }

    /**
     * Set the payment gateway
     */
    public function setGateway(string $gateway): self
    {
        $this->gateway = $this->resolveGateway($gateway);
        return $this;
    }

    /**
     * Create a payment order for an order
     */
    public function createPaymentOrder(Order $order, string $paymentMethod = 'online'): array
    {
        // For COD orders, no gateway interaction needed
        if ($paymentMethod === Payment::METHOD_COD) {
            return $this->createCODPayment($order);
        }

        try {
            DB::beginTransaction();

            // Create gateway order
            $gatewayOrder = $this->gateway->createOrder($order);

            // Create payment record
            $payment = Payment::create([
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'payment_gateway' => $this->defaultGateway,
                'payment_method' => Payment::METHOD_ONLINE,
                'gateway_order_id' => $gatewayOrder['gateway_order_id'],
                'amount' => $order->total_amount,
                'currency' => $gatewayOrder['currency'],
                'status' => Payment::STATUS_PENDING,
                'raw_payload' => ['create_order' => $gatewayOrder],
            ]);

            // Update order payment method
            $order->update([
                'payment_method' => 'online',
                'payment_status' => 'pending',
            ]);

            DB::commit();

            return [
                'payment_id' => $payment->id,
                'gateway_order_id' => $gatewayOrder['gateway_order_id'],
                'key_id' => $gatewayOrder['key_id'],
                'amount' => $gatewayOrder['amount'],
                'amount_display' => $gatewayOrder['amount_display'],
                'currency' => $gatewayOrder['currency'],
                'order_id' => $order->id,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Payment order creation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create COD payment record
     */
    protected function createCODPayment(Order $order): array
    {
        $payment = Payment::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'payment_gateway' => 'cod',
            'payment_method' => Payment::METHOD_COD,
            'amount' => $order->total_amount,
            'currency' => 'INR',
            'status' => Payment::STATUS_PENDING,
        ]);

        $order->update([
            'payment_method' => 'cod',
            'payment_status' => 'pending',
        ]);

        return [
            'payment_id' => $payment->id,
            'payment_method' => 'cod',
            'amount' => $order->total_amount,
            'order_id' => $order->id,
        ];
    }

    /**
     * Verify and complete a payment
     */
    public function verifyAndCompletePayment(Payment $payment, array $payload): bool
    {
        if (!$this->gateway->verifyPayment($payment, $payload)) {
            Log::warning('Payment verification failed', [
                'payment_id' => $payment->id,
            ]);
            return false;
        }

        return $this->markPaymentSuccessful(
            $payment,
            $payload['razorpay_payment_id'] ?? null,
            ['verification' => $payload]
        );
    }

    /**
     * Mark payment as successful
     */
    public function markPaymentSuccessful(Payment $payment, ?string $gatewayPaymentId = null, array $payload = []): bool
    {
        try {
            DB::beginTransaction();

            $payment->markAsPaid($gatewayPaymentId, $payload);

            // Update order payment status
            $payment->order->markAsPaid();

            DB::commit();

            // Dispatch event
            event(new PaymentSuccessful($payment));

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to mark payment as successful', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Mark payment as failed
     */
    public function markPaymentFailed(Payment $payment, array $payload = []): bool
    {
        $payment->markAsFailed($payload);

        $payment->order->update([
            'payment_status' => 'failed',
        ]);

        return true;
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        return $this->gateway->verifyWebhookSignature($payload, $signature);
    }

    /**
     * Process webhook payload
     */
    public function processWebhook(array $payload): array
    {
        return $this->gateway->processWebhook($payload);
    }

    /**
     * Get payment by gateway order ID
     */
    public function getPaymentByGatewayOrderId(string $gatewayOrderId): ?Payment
    {
        return Payment::where('gateway_order_id', $gatewayOrderId)->first();
    }

    /**
     * Get payment status for an order
     */
    public function getPaymentStatus(Order $order): array
    {
        $payment = $order->payment;

        if (!$payment) {
            return [
                'has_payment' => false,
                'status' => null,
            ];
        }

        return [
            'has_payment' => true,
            'payment_id' => $payment->id,
            'status' => $payment->status,
            'payment_method' => $payment->payment_method,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'paid_at' => $payment->paid_at?->toIso8601String(),
            'gateway_order_id' => $payment->gateway_order_id,
            'gateway_payment_id' => $payment->gateway_payment_id,
        ];
    }

    /**
     * Process refund
     */
    public function refund(Payment $payment, ?float $amount = null): array
    {
        if (!$payment->isPaid()) {
            throw new Exception('Cannot refund a payment that is not paid');
        }

        if ($payment->payment_method === Payment::METHOD_COD) {
            throw new Exception('Cannot refund COD payments through gateway');
        }

        try {
            $refundResponse = $this->gateway->refund($payment, $amount);

            $payment->status = Payment::STATUS_REFUNDED;
            $payment->raw_payload = array_merge(
                $payment->raw_payload ?? [],
                ['refund' => $refundResponse]
            );
            $payment->save();

            $payment->order->update([
                'payment_status' => 'refunded',
            ]);

            return $refundResponse;
        } catch (Exception $e) {
            Log::error('Refund failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
