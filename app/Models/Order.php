<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Exceptions\InvalidOrderStatusTransitionException;

class Order extends Model
{
    use HasUuids;

    /**
     * Order status constants
     */
    const STATUS_PLACED = 'placed';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Valid status transitions map
     */
    const STATUS_TRANSITIONS = [
        self::STATUS_PLACED => [self::STATUS_ACCEPTED, self::STATUS_CANCELLED],
        self::STATUS_ACCEPTED => [self::STATUS_OUT_FOR_DELIVERY, self::STATUS_CANCELLED],
        self::STATUS_OUT_FOR_DELIVERY => [self::STATUS_DELIVERED, self::STATUS_CANCELLED],
        self::STATUS_DELIVERED => [],
        self::STATUS_CANCELLED => [],
    ];

    /**
     * All valid statuses
     */
    const STATUSES = [
        self::STATUS_PLACED,
        self::STATUS_ACCEPTED,
        self::STATUS_OUT_FOR_DELIVERY,
        self::STATUS_DELIVERED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'user_id',
        'delivery_partner_id',
        'address_id',
        'status',
        'total_amount',
        'payment_method',
        'payment_status',
        'paid_at',
        'delivery_address',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    /**
     * Get the user that placed this order
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the delivery partner assigned to this order
     */
    public function deliveryPartner(): BelongsTo
    {
        return $this->belongsTo(DeliveryPartner::class);
    }

    /**
     * Get the delivery address for this order
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * Get the order items for this order
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the payment for this order
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Mark the order as paid
     */
    public function markAsPaid(): bool
    {
        $this->payment_status = 'paid';
        $this->paid_at = now();
        return $this->save();
    }

    /**
     * Check if order is paid
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if order is COD
     */
    public function isCOD(): bool
    {
        return $this->payment_method === 'cod';
    }

    /**
     * Check if a status transition is valid
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $allowedTransitions = self::STATUS_TRANSITIONS[$this->status] ?? [];
        return in_array($newStatus, $allowedTransitions);
    }

    /**
     * Get the allowed transitions from current status
     */
    public function getAllowedTransitions(): array
    {
        return self::STATUS_TRANSITIONS[$this->status] ?? [];
    }

    /**
     * Transition to a new status with validation
     *
     * @throws InvalidOrderStatusTransitionException
     */
    public function transitionTo(string $newStatus): bool
    {
        if (!$this->canTransitionTo($newStatus)) {
            throw new InvalidOrderStatusTransitionException(
                "Cannot transition from '{$this->status}' to '{$newStatus}'"
            );
        }

        // Cannot mark as delivered without a delivery partner
        if ($newStatus === self::STATUS_DELIVERED && is_null($this->delivery_partner_id)) {
            throw new InvalidOrderStatusTransitionException(
                "Cannot mark order as delivered without an assigned delivery partner"
            );
        }

        $this->status = $newStatus;
        return $this->save();
    }

    /**
     * Check if the order can be assigned to a delivery partner
     * Cannot assign if: delivered, cancelled, or out_for_delivery
     */
    public function canBeAssigned(): bool
    {
        return !in_array($this->status, [
            self::STATUS_OUT_FOR_DELIVERY,
            self::STATUS_DELIVERED,
            self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Check if the order is in a terminal state
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, [self::STATUS_DELIVERED, self::STATUS_CANCELLED]);
    }

    /**
     * Assign a delivery partner to this order
     *
     * @throws InvalidOrderStatusTransitionException
     */
    public function assignDeliveryPartner(DeliveryPartner $partner): bool
    {
        if (!$this->canBeAssigned()) {
            throw new InvalidOrderStatusTransitionException(
                "Cannot assign delivery partner to an order with status '{$this->status}'"
            );
        }

        if (!$partner->canBeAssigned()) {
            throw new InvalidOrderStatusTransitionException(
                "Delivery partner is not available for assignment"
            );
        }

        // Clear previous partner's assignment if exists
        if ($this->delivery_partner_id && $this->deliveryPartner) {
            $this->deliveryPartner->clearAssignment();
        }

        $this->delivery_partner_id = $partner->id;
        $this->save();

        $partner->assignOrder($this);

        return true;
    }

    /**
     * Unassign the current delivery partner
     */
    public function unassignDeliveryPartner(): bool
    {
        if ($this->delivery_partner_id && $this->deliveryPartner) {
            $this->deliveryPartner->clearAssignment();
        }

        $this->delivery_partner_id = null;
        return $this->save();
    }

    /**
     * Scope for orders that need assignment
     */
    public function scopeNeedsAssignment($query)
    {
        return $query->whereNull('delivery_partner_id')
            ->whereNotIn('status', [self::STATUS_DELIVERED, self::STATUS_CANCELLED]);
    }

    /**
     * Scope for active orders (not delivered or cancelled)
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_DELIVERED, self::STATUS_CANCELLED]);
    }

    /**
     * Scope for orders by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
