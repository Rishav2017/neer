<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'user_addresses';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'label',
        'address_line',
        'area_name',
        'landmark',
        'receiver_name',
        'receiver_phone',
        'latitude',
        'longitude',
        'pincode',
        'is_default',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_default' => 'boolean',
    ];

    /**
     * Get the user that owns the address.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include default addresses.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // When saving an address as default, unset other defaults for the user
        static::saving(function ($address) {
            if ($address->is_default) {
                static::where('user_id', $address->user_id)
                    ->where('id', '!=', $address->id ?? 0)
                    ->update(['is_default' => false]);
            }
        });

        // If this is the first address for a user, make it default
        static::creating(function ($address) {
            $existingCount = static::where('user_id', $address->user_id)->count();
            if ($existingCount === 0) {
                $address->is_default = true;
            }
        });
    }
}
