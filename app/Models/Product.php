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
     * Get the sub-sub-category this product belongs to (level 2)
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'sub_category_id');
    }

    /**
     * Alias for subcategory - the sub-sub-category (level 2)
     */
    public function subSubcategory(): BelongsTo
    {
        return $this->subcategory();
    }

    /**
     * Get the sub-category (level 1) - parent of sub-sub-category
     */
    public function getParentCategoryAttribute()
    {
        $subSubCategory = $this->getRelationValue('subcategory');
        return $subSubCategory?->parent;
    }

    /**
     * Get the top-level category (level 0) - grandparent of sub-sub-category
     */
    public function getRootCategoryAttribute()
    {
        $subSubCategory = $this->getRelationValue('subcategory');
        return $subSubCategory?->parent?->parent;
    }

    /**
     * Get the full category path as a string
     */
    public function getCategoryPathAttribute(): string
    {
        $subSubCategory = $this->getRelationValue('subcategory');
        if (!$subSubCategory) {
            return 'N/A';
        }

        return $subSubCategory->full_path;
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
