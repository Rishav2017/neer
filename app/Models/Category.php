<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'description',
        'parent_id',
    ];

    /**
     * Get subcategories (categories that have this category as parent)
     */
    public function subcategories(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Get parent category
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get products in this subcategory (only if this is a subcategory)
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'sub_category_id');
    }

    /**
     * Check if this is a top-level category
     */
    public function isTopLevel(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Check if this is a subcategory
     */
    public function isSubcategory(): bool
    {
        return $this->parent_id !== null;
    }
}
