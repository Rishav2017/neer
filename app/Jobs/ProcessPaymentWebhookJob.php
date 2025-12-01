<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPaymentWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $gateway,
        public array $payload
    ) {
        $this->onQueue('payments');
    }

    /**
     * Execute the job.
     */
    public function handle(PaymentService $paymentService): void
    {
        Log::info('Processing payment webhook', [
            'gateway' => $this->gateway,
            'event' => $this->payload['event'] ?? 'unknown',
        ]);

        try {
            $processedData = $paymentService->processWebhook($this->payload);
            $event = $processedData['event'] ?? '';

            switch ($event) {
                case 'payment.captured':
                case 'payment.authorized':
                    $this->handlePaymentSuccess($paymentService, $processedData);
                    break;

                case 'payment.failed':
                    $this->handlePaymentFailure($paymentService, $processedData);
                    break;

                case 'refund.created':
                case 'refund.processed':
                    $this->handleRefund($processedData);
                    break;

                default:
                    Log::info('Unhandled webhook event', [
                        'event' => $event,
                        'gateway' => $this->gateway,
                    ]);
            }
        } catch (\Exception $e) {
            Log::error('Payment webhook processing failed', [
                'gateway' => $this->gateway,
                'error' => $e->getMessage(),
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff);
            }
        }
    }

    /**
     * Handle successful payment
     */
    protected function handlePaymentSuccess(PaymentService $paymentService, array $data): void
    {
        $gatewayOrderId = $data['order_id'] ?? null;
        $gatewayPaymentId = $data['payment_id'] ?? null;

        if (!$gatewayOrderId) {
            Log::warning('Payment success webhook missing order_id', $data);
            return;
        }

        $payment = Payment::where('gateway_order_id', $gatewayOrderId)->first();

        if (!$payment) {
            Log::warning('Payment not found for webhook', [
                'gateway_order_id' => $gatewayOrderId,
            ]);
            return;
        }

        if ($payment->isPaid()) {
            Log::info('Payment already marked as paid', [
                'payment_id' => $payment->id,
            ]);
            return;
        }

        $paymentService->markPaymentSuccessful($payment, $gatewayPaymentId, ['webhook' => $data]);

        Log::info('Payment marked as successful via webhook', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
        ]);
    }

    /**
     * Handle failed payment
     */
    protected function handlePaymentFailure(PaymentService $paymentService, array $data): void
    {
        $gatewayOrderId = $data['order_id'] ?? null;

        if (!$gatewayOrderId) {
            Log::warning('Payment failure webhook missing order_id', $data);
            return;
        }

        $payment = Payment::where('gateway_order_id', $gatewayOrderId)->first();

        if (!$payment) {
            Log::warning('Payment not found for failure webhook', [
                'gateway_order_id' => $gatewayOrderId,
            ]);
            return;
        }

        $paymentService->markPaymentFailed($payment, ['webhook' => $data]);

        Log::info('Payment marked as failed via webhook', [
            'payment_id' => $payment->id,
            'error_code' => $data['error_code'] ?? 'unknown',
        ]);
    }

    /**
     * Handle refund webhook
     */
    protected function handleRefund(array $data): void
    {
        Log::info('Refund webhook received', [
            'data' => $data,
        ]);

        // Additional refund handling logic can be added here
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Payment webhook job failed permanently', [
            'gateway' => $this->gateway,
            'error' => $exception->getMessage(),
            'payload' => $this->payload,
        ]);
    }
}
