<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdated;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendOrderStatusNotification implements ShouldQueue
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
    public function handle(OrderStatusUpdated $event): void
    {
        $order = $event->order;
        $user = $order->user;

        if (!$user) {
            Log::warning('Cannot send order status notification: user not found', [
                'order_id' => $order->id,
            ]);
            return;
        }

        Log::info('Sending order status notification', [
            'order_id' => $order->id,
            'user_id' => $user->id,
            'previous_status' => $event->previousStatus,
            'new_status' => $event->newStatus,
        ]);

        $this->notificationService->sendOrderStatusUpdate(
            $user,
            $order->id,
            $event->newStatus
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(OrderStatusUpdated $event, \Throwable $exception): void
    {
        Log::error('Failed to send order status notification', [
            'order_id' => $event->order->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
