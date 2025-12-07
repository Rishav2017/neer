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
        'level',
    ];

    protected $casts = [
        'level' => 'integer',
    ];

    /**
     * Get subcategories/children (categories that have this category as parent)
     */
    public function subcategories(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Alias for subcategories - for semantic clarity
     */
    public function children(): HasMany
    {
        return $this->subcategories();
    }

    /**
     * Get parent category
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get products in this category (only sub-sub-categories should have products)
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'sub_category_id');
    }

    /**
     * Check if this is a top-level category (level 0)
     */
    public function isTopLevel(): bool
    {
        return $this->level === 0;
    }

    /**
     * Check if this is a subcategory (level 1)
     */
    public function isSubcategory(): bool
    {
        return $this->level === 1;
    }

    /**
     * Check if this is a sub-sub-category (level 2)
     */
    public function isSubSubcategory(): bool
    {
        return $this->level === 2;
    }

    /**
     * Check if this category can have products (only level 2)
     */
    public function canHaveProducts(): bool
    {
        return $this->level === 2;
    }

    /**
     * Check if this category can have children (level 0 and 1 only)
     */
    public function canHaveChildren(): bool
    {
        return $this->level < 2;
    }

    /**
     * Get the full hierarchy path as an array
     */
    public function getHierarchyPath(): array
    {
        $path = [$this];
        $current = $this;

        while ($current->parent) {
            array_unshift($path, $current->parent);
            $current = $current->parent;
        }

        return $path;
    }

    /**
     * Get the full hierarchy path as a string
     */
    public function getFullPathAttribute(): string
    {
        return collect($this->getHierarchyPath())
            ->pluck('name')
            ->implode(' > ');
    }

    /**
     * Get all descendant categories (recursive)
     */
    public function getAllDescendants(): \Illuminate\Database\Eloquent\Collection
    {
        $descendants = collect();

        foreach ($this->subcategories as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getAllDescendants());
        }

        return $descendants;
    }

    /**
     * Count all products in this category and its descendants
     */
    public function getTotalProductsCountAttribute(): int
    {
        $count = $this->products()->count();

        foreach ($this->subcategories as $child) {
            $count += $child->total_products_count;
        }

        return $count;
    }
}
