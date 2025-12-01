<?php

namespace App\Services\Notification;

use App\Models\User;

interface NotificationChannelInterface
{
    /**
     * Send a notification through this channel
     *
     * @param User $user
     * @param string $title
     * @param string $body
     * @param array $data Additional data to send with notification
     * @return array ['success' => bool, 'response' => array]
     */
    public function send(User $user, string $title, string $body, array $data = []): array;

    /**
     * Check if the channel is available for this user
     *
     * @param User $user
     * @return bool
     */
    public function isAvailable(User $user): bool;

    /**
     * Get channel name
     *
     * @return string
     */
    public function getChannelName(): string;
}
