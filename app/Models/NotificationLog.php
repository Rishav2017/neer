<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasUuids;

    /**
     * The table associated with the model.
     */
    protected $table = 'notifications_log';

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';

    /**
     * Channel constants
     */
    const CHANNEL_PUSH = 'push';
    const CHANNEL_SMS = 'sms';
    const CHANNEL_EMAIL = 'email';

    protected $fillable = [
        'user_id',
        'channel',
        'title',
        'body',
        'status',
        'response_json',
        'metadata',
    ];

    protected $casts = [
        'response_json' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the user this notification was sent to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark notification as sent
     */
    public function markAsSent(array $response = []): bool
    {
        $this->status = self::STATUS_SENT;
        $this->response_json = $response;
        return $this->save();
    }

    /**
     * Mark notification as failed
     */
    public function markAsFailed(array $response = []): bool
    {
        $this->status = self::STATUS_FAILED;
        $this->response_json = $response;
        return $this->save();
    }

    /**
     * Scope for sent notifications
     */
    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Scope for failed notifications
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope by channel
     */
    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }
}
