<?php

namespace App\Services\Notification;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoPushChannel implements NotificationChannelInterface
{
    protected string $expoApiUrl = 'https://exp.host/--/api/v2/push/send';

    /**
     * Send push notification via Expo
     */
    public function send(User $user, string $title, string $body, array $data = []): array
    {
        if (!$this->isAvailable($user)) {
            return [
                'success' => false,
                'response' => ['error' => 'User does not have a valid Expo push token'],
            ];
        }

        $payload = [
            'to' => $user->expo_push_token,
            'title' => $title,
            'body' => $body,
            'sound' => 'default',
            'priority' => 'high',
        ];

        if (!empty($data)) {
            $payload['data'] = $data;
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip, deflate',
                'Content-Type' => 'application/json',
            ])->post($this->expoApiUrl, $payload);

            $responseData = $response->json();

            // Check for errors in Expo response
            if (isset($responseData['data'][0]['status']) && $responseData['data'][0]['status'] === 'error') {
                Log::warning('Expo push notification failed', [
                    'user_id' => $user->id,
                    'error' => $responseData['data'][0]['message'] ?? 'Unknown error',
                ]);

                return [
                    'success' => false,
                    'response' => $responseData,
                ];
            }

            Log::info('Expo push notification sent', [
                'user_id' => $user->id,
                'ticket_id' => $responseData['data'][0]['id'] ?? null,
            ]);

            return [
                'success' => true,
                'response' => $responseData,
            ];
        } catch (\Exception $e) {
            Log::error('Expo push notification exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'response' => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Check if user has a valid Expo token
     */
    public function isAvailable(User $user): bool
    {
        return $user->hasExpoPushToken();
    }

    /**
     * Validate Expo push token format
     */
    public function validateToken(string $token): bool
    {
        return preg_match('/^ExponentPushToken\[.+\]$|^ExpoPushToken\[.+\]$/', $token);
    }

    /**
     * Get channel name
     */
    public function getChannelName(): string
    {
        return 'push';
    }

    /**
     * Send bulk notifications
     */
    public function sendBulk(array $notifications): array
    {
        $payload = array_map(function ($notification) {
            return [
                'to' => $notification['token'],
                'title' => $notification['title'],
                'body' => $notification['body'],
                'sound' => 'default',
                'priority' => 'high',
                'data' => $notification['data'] ?? [],
            ];
        }, $notifications);

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip, deflate',
                'Content-Type' => 'application/json',
            ])->post($this->expoApiUrl, $payload);

            return [
                'success' => true,
                'response' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('Expo bulk push notification failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'response' => ['error' => $e->getMessage()],
            ];
        }
    }
}
