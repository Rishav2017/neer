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

        $category = Category::create($validated);

        return $this->success($category, 'Category created successfully', 201);
    }

    /**
     * Create a subcategory under a category (Admin only)
     */
    public function storeSubcategory(Request $request, string $id)
    {
        $parentCategory = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $subcategory = Category::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'parent_id' => $parentCategory->id,
        ]);

        return $this->success($subcategory, 'Subcategory created successfully', 201);
    }

    /**
     * List all categories with their subcategories (Public)
     */
    public function index()
    {
        $categories = Category::with('subcategories')
            ->whereNull('parent_id')
            ->get();

        return $this->success($categories, 'Categories fetched successfully');
    }

    /**
     * Update a category or subcategory (Admin only)
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
                },
            ],
        ]);

        $category->update($validated);

        return $this->success($category->fresh(), 'Category updated successfully');
    }

    /**
     * Delete a category or subcategory (Admin only)
     */
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);

        // Check if category has subcategories or products
        if ($category->subcategories()->count() > 0) {
            return $this->error('Cannot delete category with subcategories. Please delete subcategories first.', 422);
        }

        if ($category->products()->count() > 0) {
            return $this->error('Cannot delete category with products. Please delete or move products first.', 422);
        }

        $category->delete();

        return $this->success(null, 'Category deleted successfully');
    }
}
