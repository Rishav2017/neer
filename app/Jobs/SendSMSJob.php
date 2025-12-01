<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Models\User;
use App\Services\Notification\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSMSJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $notificationLogId,
        public string $userId,
        public string $message
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $log = NotificationLog::find($this->notificationLogId);
        $user = User::find($this->userId);

        if (!$log || !$user) {
            Log::warning('SMS job failed: missing log or user', [
                'log_id' => $this->notificationLogId,
                'user_id' => $this->userId,
            ]);
            return;
        }

        $channel = new SmsChannel();

        if (!$channel->isAvailable($user)) {
            Log::info('SMS skipped: user has no valid phone number', [
                'user_id' => $user->id,
            ]);
            $log->markAsFailed(['error' => 'User has no valid phone number']);
            return;
        }

        $result = $channel->send($user, '', $this->message);

        if ($result['success']) {
            $log->markAsSent($result['response']);
            Log::info('SMS sent successfully', [
                'user_id' => $user->id,
                'log_id' => $log->id,
            ]);
        } else {
            $log->markAsFailed($result['response']);
            Log::warning('SMS failed', [
                'user_id' => $user->id,
                'log_id' => $log->id,
                'error' => $result['response'],
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff);
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $log = NotificationLog::find($this->notificationLogId);

        if ($log) {
            $log->markAsFailed(['error' => $exception->getMessage()]);
        }

        Log::error('SMS job failed permanently', [
            'log_id' => $this->notificationLogId,
            'error' => $exception->getMessage(),
        ]);
    }
}
