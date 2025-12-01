<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryPartner extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'phone',
        'status',
        'current_order_id',
        'location_lat',
        'location_lng',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'location_lat' => 'float',
        'location_lng' => 'float',
    ];

    /**
     * Status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    /**
     * Get all orders assigned to this partner (historical)
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the current active order
     */
    public function activeOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'current_order_id');
    }

    /**
     * Scope to get only active partners
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get only available partners (active and not currently assigned)
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereNull('current_order_id');
    }

    /**
     * Check if the partner is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if the partner is available for assignment
     */
    public function isAvailable(): bool
    {
        return $this->isActive() && is_null($this->current_order_id);
    }

    /**
     * Check if the partner can be assigned to a new order
     */
    public function canBeAssigned(): bool
    {
        return $this->isAvailable();
    }

    /**
     * Assign an order to this partner
     */
    public function assignOrder(Order $order): bool
    {
        if (!$this->canBeAssigned()) {
            return false;
        }

        $this->current_order_id = $order->id;
        return $this->save();
    }

    /**
     * Clear the current order assignment
     */
    public function clearAssignment(): bool
    {
        $this->current_order_id = null;
        return $this->save();
    }

    /**
     * Update the partner's location
     */
    public function updateLocation(float $lat, float $lng): bool
    {
        $this->location_lat = $lat;
        $this->location_lng = $lng;
        return $this->save();
    }
}
