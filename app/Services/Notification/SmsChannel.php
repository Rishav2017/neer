<?php

namespace App\Services\Notification;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * SMS Channel - Placeholder for future SMS provider integration
 *
 * This class provides the structure for integrating SMS providers like:
 * - Twilio
 * - MSG91
 * - AWS SNS
 * - Nexmo/Vonage
 *
 * To implement a specific provider:
 * 1. Add provider credentials to config/services.php
 * 2. Implement the send() method with provider's SDK/API
 */
class SmsChannel implements NotificationChannelInterface
{
    protected string $provider;

    public function __construct()
    {
        $this->provider = config('services.sms.provider', 'twilio');
    }

    /**
     * Send SMS notification
     *
     * @param User $user
     * @param string $title Not used for SMS, but required by interface
     * @param string $body The SMS message content
     * @param array $data Additional data (optional)
     * @return array
     */
    public function send(User $user, string $title, string $body, array $data = []): array
    {
        if (!$this->isAvailable($user)) {
            return [
                'success' => false,
                'response' => ['error' => 'User does not have a valid phone number'],
            ];
        }

        // Placeholder - implement actual SMS sending based on provider
        return $this->sendSms($user->phone, $body);
    }

    /**
     * Send SMS to a phone number
     *
     * @param string $phone
     * @param string $message
     * @return array
     */
    public function sendSms(string $phone, string $message): array
    {
        Log::info('SMS sending placeholder', [
            'phone' => $this->maskPhone($phone),
            'message_length' => strlen($message),
            'provider' => $this->provider,
        ]);

        // TODO: Implement actual SMS sending based on provider
        // Example implementations:

        // For Twilio:
        // return $this->sendViaTwilio($phone, $message);

        // For MSG91:
        // return $this->sendViaMSG91($phone, $message);

        // Placeholder response
        return [
            'success' => true,
            'response' => [
                'status' => 'placeholder',
                'message' => 'SMS provider not configured. Message logged.',
                'phone' => $this->maskPhone($phone),
            ],
        ];
    }

    /**
     * Check if SMS can be sent to user
     */
    public function isAvailable(User $user): bool
    {
        return !empty($user->phone) && strlen($user->phone) >= 10;
    }

    /**
     * Get channel name
     */
    public function getChannelName(): string
    {
        return 'sms';
    }

    /**
     * Mask phone number for logging
     */
    protected function maskPhone(string $phone): string
    {
        $length = strlen($phone);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }
        return substr($phone, 0, 2) . str_repeat('*', $length - 4) . substr($phone, -2);
    }

    /**
     * Placeholder: Send via Twilio
     *
     * @param string $phone
     * @param string $message
     * @return array
     */
    protected function sendViaTwilio(string $phone, string $message): array
    {
        // Uncomment and configure when implementing:
        /*
        $twilioSid = config('services.twilio.sid');
        $twilioToken = config('services.twilio.token');
        $twilioFrom = config('services.twilio.from');

        $client = new \Twilio\Rest\Client($twilioSid, $twilioToken);

        try {
            $result = $client->messages->create($phone, [
                'from' => $twilioFrom,
                'body' => $message,
            ]);

            return [
                'success' => true,
                'response' => [
                    'sid' => $result->sid,
                    'status' => $result->status,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Twilio SMS failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'response' => ['error' => $e->getMessage()],
            ];
        }
        */

        return ['success' => false, 'response' => ['error' => 'Twilio not configured']];
    }

    /**
     * Placeholder: Send via MSG91
     *
     * @param string $phone
     * @param string $message
     * @return array
     */
    protected function sendViaMSG91(string $phone, string $message): array
    {
        // Uncomment and configure when implementing:
        /*
        $authKey = config('services.msg91.auth_key');
        $senderId = config('services.msg91.sender_id');

        try {
            $response = Http::withHeaders([
                'authkey' => $authKey,
            ])->post('https://api.msg91.com/api/v5/flow/', [
                'sender' => $senderId,
                'mobiles' => $phone,
                'message' => $message,
            ]);

            return [
                'success' => $response->successful(),
                'response' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('MSG91 SMS failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'response' => ['error' => $e->getMessage()],
            ];
        }
        */

        return ['success' => false, 'response' => ['error' => 'MSG91 not configured']];
    }
}
