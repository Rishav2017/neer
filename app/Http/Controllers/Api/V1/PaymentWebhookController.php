<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessPaymentWebhookJob;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Handle Razorpay webhook
     * POST /payments/webhook
     */
    public function handleRazorpay(Request $request): JsonResponse
    {
        $rawPayload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature', '');

        // Verify webhook signature
        if (!$this->paymentService->verifyWebhookSignature($rawPayload, $signature)) {
            Log::warning('Invalid Razorpay webhook signature', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['status' => 'invalid_signature'], 400);
        }

        $payload = $request->all();

        Log::info('Razorpay webhook received', [
            'event' => $payload['event'] ?? 'unknown',
        ]);

        // Dispatch job to process webhook asynchronously
        ProcessPaymentWebhookJob::dispatch('razorpay', $payload);

        // Return immediately - processing happens in background
        return response()->json(['status' => 'received'], 200);
    }

    /**
     * Handle generic webhook (for future gateways)
     * POST /payments/webhook/{gateway}
     */
    public function handle(string $gateway, Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info("Payment webhook received for {$gateway}", [
            'event' => $payload['event'] ?? $payload['type'] ?? 'unknown',
        ]);

        // Dispatch job to process webhook asynchronously
        ProcessPaymentWebhookJob::dispatch($gateway, $payload);

        return response()->json(['status' => 'received'], 200);
    }
}
