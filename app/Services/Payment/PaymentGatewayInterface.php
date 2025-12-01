<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;

interface PaymentGatewayInterface
{
    /**
     * Create a payment order with the gateway
     *
     * @param Order $order
     * @param array $options
     * @return array ['gateway_order_id' => string, 'amount' => int, 'currency' => string, ...]
     */
    public function createOrder(Order $order, array $options = []): array;

    /**
     * Verify a payment
     *
     * @param Payment $payment
     * @param array $payload
     * @return bool
     */
    public function verifyPayment(Payment $payment, array $payload): bool;

    /**
     * Verify webhook signature
     *
     * @param string $payload
     * @param string $signature
     * @return bool
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool;

    /**
     * Process webhook payload
     *
     * @param array $payload
     * @return array ['event' => string, 'payment_id' => string, 'order_id' => string, ...]
     */
    public function processWebhook(array $payload): array;

    /**
     * Refund a payment
     *
     * @param Payment $payment
     * @param float|null $amount
     * @return array
     */
    public function refund(Payment $payment, ?float $amount = null): array;

    /**
     * Get payment status from gateway
     *
     * @param string $gatewayPaymentId
     * @return array
     */
    public function getPaymentStatus(string $gatewayPaymentId): array;
}
