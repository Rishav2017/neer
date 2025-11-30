<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasUuids;

    protected $fillable = [
        'sub_category_id',
        'name',
        'description',
        'price',
        'stock_quantity',
        'image_url',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_quantity' => 'integer',
    ];

    /**
     * Get the subcategory this product belongs to
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'sub_category_id');
    }

    /**
     * Get the parent category through subcategory
     * This is a helper method to access the parent category
     */
    public function getCategoryAttribute()
    {
        return $this->subcategory?->parent;
    }

    /**
     * Get cart items for this product
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get order items for this product
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Check if product is in stock
     */
    public function isInStock(int $quantity = 1): bool
    {
        return $this->stock_quantity >= $quantity;
    }
}
