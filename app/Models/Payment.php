<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUuids;

    /**
     * Payment status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';

    /**
     * Payment method constants
     */
    const METHOD_COD = 'cod';
    const METHOD_ONLINE = 'online';

    /**
     * Payment gateway constants
     */
    const GATEWAY_RAZORPAY = 'razorpay';
    const GATEWAY_STRIPE = 'stripe';

    protected $fillable = [
        'user_id',
        'order_id',
        'payment_gateway',
        'payment_method',
        'gateway_order_id',
        'gateway_payment_id',
        'amount',
        'currency',
        'status',
        'raw_payload',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'raw_payload' => 'array',
        'paid_at' => 'datetime',
    ];

    /**
     * Get the user that made this payment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order this payment is for
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Check if payment is successful
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if payment failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Mark payment as paid
     */
    public function markAsPaid(string $gatewayPaymentId = null, array $payload = []): bool
    {
        $this->status = self::STATUS_PAID;
        $this->paid_at = now();

        if ($gatewayPaymentId) {
            $this->gateway_payment_id = $gatewayPaymentId;
        }

        if (!empty($payload)) {
            $this->raw_payload = array_merge($this->raw_payload ?? [], $payload);
        }

        return $this->save();
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed(array $payload = []): bool
    {
        $this->status = self::STATUS_FAILED;

        if (!empty($payload)) {
            $this->raw_payload = array_merge($this->raw_payload ?? [], $payload);
        }

        return $this->save();
    }

    /**
     * Scope for pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for successful payments
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope for failed payments
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope by gateway
     */
    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('payment_gateway', $gateway);
    }
}
