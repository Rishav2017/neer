<?php

namespace App\Services\Notification;

use App\Jobs\SendPushNotificationJob;
use App\Jobs\SendSMSJob;
use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected ExpoPushChannel $pushChannel;
    protected SmsChannel $smsChannel;

    public function __construct()
    {
        $this->pushChannel = new ExpoPushChannel();
        $this->smsChannel = new SmsChannel();
    }

    /**
     * Send a push notification (queued)
     *
     * @param User $user
     * @param string $title
     * @param string $body
     * @param array $data
     * @return NotificationLog
     */
    public function sendPush(User $user, string $title, string $body, array $data = []): NotificationLog
    {
        // Create pending log entry
        $log = NotificationLog::create([
            'user_id' => $user->id,
            'channel' => NotificationLog::CHANNEL_PUSH,
            'title' => $title,
            'body' => $body,
            'status' => NotificationLog::STATUS_PENDING,
            'metadata' => $data,
        ]);

        // Dispatch to queue
        SendPushNotificationJob::dispatch($log->id, $user->id, $title, $body, $data);

        return $log;
    }

    /**
     * Send a push notification immediately (not queued)
     *
     * @param User $user
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array
     */
    public function sendPushNow(User $user, string $title, string $body, array $data = []): array
    {
        $log = NotificationLog::create([
            'user_id' => $user->id,
            'channel' => NotificationLog::CHANNEL_PUSH,
            'title' => $title,
            'body' => $body,
            'status' => NotificationLog::STATUS_PENDING,
            'metadata' => $data,
        ]);

        $result = $this->pushChannel->send($user, $title, $body, $data);

        if ($result['success']) {
            $log->markAsSent($result['response']);
        } else {
            $log->markAsFailed($result['response']);
        }

        return $result;
    }

    /**
     * Send an SMS notification (queued)
     *
     * @param User $user
     * @param string $message
     * @param array $data
     * @return NotificationLog
     */
    public function sendSms(User $user, string $message, array $data = []): NotificationLog
    {
        $log = NotificationLog::create([
            'user_id' => $user->id,
            'channel' => NotificationLog::CHANNEL_SMS,
            'title' => 'SMS',
            'body' => $message,
            'status' => NotificationLog::STATUS_PENDING,
            'metadata' => $data,
        ]);

        SendSMSJob::dispatch($log->id, $user->id, $message);

        return $log;
    }

    /**
     * Send SMS immediately (not queued)
     *
     * @param User $user
     * @param string $message
     * @return array
     */
    public function sendSmsNow(User $user, string $message): array
    {
        $log = NotificationLog::create([
            'user_id' => $user->id,
            'channel' => NotificationLog::CHANNEL_SMS,
            'title' => 'SMS',
            'body' => $message,
            'status' => NotificationLog::STATUS_PENDING,
        ]);

        $result = $this->smsChannel->send($user, '', $message);

        if ($result['success']) {
            $log->markAsSent($result['response']);
        } else {
            $log->markAsFailed($result['response']);
        }

        return $result;
    }

    /**
     * Send notification through multiple channels
     *
     * @param User $user
     * @param string $title
     * @param string $body
     * @param array $channels ['push', 'sms']
     * @param array $data
     * @return array
     */
    public function sendMultiChannel(User $user, string $title, string $body, array $channels = ['push'], array $data = []): array
    {
        $results = [];

        foreach ($channels as $channel) {
            switch ($channel) {
                case 'push':
                    $results['push'] = $this->sendPush($user, $title, $body, $data);
                    break;
                case 'sms':
                    $results['sms'] = $this->sendSms($user, $body, $data);
                    break;
            }
        }

        return $results;
    }

    /**
     * Send order status update notification
     *
     * @param User $user
     * @param string $orderId
     * @param string $status
     * @return NotificationLog
     */
    public function sendOrderStatusUpdate(User $user, string $orderId, string $status): NotificationLog
    {
        $statusMessages = [
            'placed' => 'Your order has been placed successfully!',
            'accepted' => 'Your order has been accepted and is being prepared.',
            'out_for_delivery' => 'Your order is out for delivery!',
            'delivered' => 'Your order has been delivered. Enjoy!',
            'cancelled' => 'Your order has been cancelled.',
        ];

        $title = 'Order Update';
        $body = $statusMessages[$status] ?? "Your order status is now: {$status}";

        return $this->sendPush($user, $title, $body, [
            'type' => 'order_status',
            'order_id' => $orderId,
            'status' => $status,
        ]);
    }

    /**
     * Send payment success notification
     *
     * @param User $user
     * @param string $orderId
     * @param float $amount
     * @return NotificationLog
     */
    public function sendPaymentSuccessNotification(User $user, string $orderId, float $amount): NotificationLog
    {
        $title = 'Payment Successful';
        $body = "Your payment of ₹{$amount} has been received successfully.";

        return $this->sendPush($user, $title, $body, [
            'type' => 'payment_success',
            'order_id' => $orderId,
            'amount' => $amount,
        ]);
    }

    /**
     * Validate Expo token
     *
     * @param string $token
     * @return bool
     */
    public function validateExpoToken(string $token): bool
    {
        return $this->pushChannel->validateToken($token);
    }

    /**
     * Get channel instance
     *
     * @param string $channel
     * @return NotificationChannelInterface
     */
    public function getChannel(string $channel): NotificationChannelInterface
    {
        return match ($channel) {
            'push' => $this->pushChannel,
            'sms' => $this->smsChannel,
            default => throw new \InvalidArgumentException("Unknown channel: {$channel}"),
        };
    }
}
