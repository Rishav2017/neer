<?php

namespace App\Listeners;

use App\Events\PaymentSuccessful;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendPaymentSuccessNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The queue connection to use.
     */
    public string $connection = 'redis';

    /**
     * The queue to use.
     */
    public string $queue = 'notifications';

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    protected NotificationService $notificationService;

    /**
     * Create the event listener.
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentSuccessful $event): void
    {
        $payment = $event->payment;
        $user = $payment->user;

        if (!$user) {
            Log::warning('Cannot send payment notification: user not found', [
                'payment_id' => $payment->id,
            ]);
            return;
        }

        Log::info('Sending payment success notification', [
            'payment_id' => $payment->id,
            'user_id' => $user->id,
            'amount' => $payment->amount,
        ]);

        $this->notificationService->sendPaymentSuccessNotification(
            $user,
            $payment->order_id,
            (float) $payment->amount
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(PaymentSuccessful $event, \Throwable $exception): void
    {
        Log::error('Failed to send payment success notification', [
            'payment_id' => $event->payment->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
