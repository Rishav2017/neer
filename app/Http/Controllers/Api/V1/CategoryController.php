<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    use ApiResponse;

    /**
     * Create a top-level category (Admin only)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['level'] = 0;
        $category = Category::create($validated);

        return $this->success($category, 'Category created successfully', 201);
    }

    /**
     * Create a child category under a parent (Admin only)
     * Creates subcategory (level 1) or sub-subcategory (level 2)
     */
    public function storeSubcategory(Request $request, string $id)
    {
        $parentCategory = Category::findOrFail($id);

        // Check if parent can have children (only level 0 and 1)
        if ($parentCategory->level >= 2) {
            return $this->error('Cannot add children to a sub-subcategory. Maximum depth is 3 levels.', 422);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $childLevel = $parentCategory->level + 1;
        $subcategory = Category::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'parent_id' => $parentCategory->id,
            'level' => $childLevel,
        ]);

        $typeNames = ['Category', 'Subcategory', 'Sub-subcategory'];
        $typeName = $typeNames[$childLevel] ?? 'Category';

        return $this->success($subcategory, "{$typeName} created successfully", 201);
    }

    /**
     * List all categories with full 3-level hierarchy (Public)
     */
    public function index()
    {
        $categories = Category::with(['subcategories.subcategories'])
            ->where('level', 0)
            ->get();

        return $this->success($categories, 'Categories fetched successfully');
    }

    /**
     * Update a category (Admin only)
     */
    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => [
                'nullable',
                Rule::exists('categories', 'id'),
                function ($attribute, $value, $fail) use ($category, $id) {
                    if ($value === $id) {
                        $fail('A category cannot be its own parent.');
                    }

                    if ($value) {
                        $newParent = Category::find($value);

                        // Check for circular reference
                        $current = $newParent;
                        while ($current) {
                            if ($current->id === $category->id) {
                                $fail('Cannot set a descendant as the parent.');
                                return;
                            }
                            $current = $current->parent;
                        }

                        // Check level constraints
                        if ($newParent->level >= 2) {
                            $fail('Cannot add children to a sub-subcategory.');
                            return;
                        }
                    }
                },
            ],
        ]);

        // Calculate new level if parent changed
        if (array_key_exists('parent_id', $validated)) {
            if ($validated['parent_id']) {
                $parent = Category::find($validated['parent_id']);
                $validated['level'] = $parent->level + 1;
            } else {
                $validated['level'] = 0;
            }

            // Update children levels recursively
            if ($validated['level'] !== $category->level) {
                $this->updateChildrenLevels($category, $validated['level']);
            }
        }

        $category->update($validated);

        return $this->success($category->fresh()->load('parent'), 'Category updated successfully');
    }

    /**
     * Delete a category (Admin only)
     */
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);

        // Check if category has subcategories
        if ($category->subcategories()->count() > 0) {
            $childType = $category->level === 0 ? 'subcategories' : 'sub-subcategories';
            return $this->error("Cannot delete category with {$childType}. Please delete them first.", 422);
        }

        // Check if category has products (only level 2 should have products)
        if ($category->products()->count() > 0) {
            return $this->error('Cannot delete category with products. Please delete or move products first.', 422);
        }

        $category->delete();

        return $this->success(null, 'Category deleted successfully');
    }

    /**
     * Recursively update children levels when a category's level changes
     */
    private function updateChildrenLevels(Category $category, int $newParentLevel): void
    {
        foreach ($category->subcategories as $child) {
            $child->level = $newParentLevel + 1;
            $child->save();
            $this->updateChildrenLevels($child, $child->level);
        }
    }
}
