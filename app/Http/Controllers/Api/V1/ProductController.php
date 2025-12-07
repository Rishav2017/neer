<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    use ApiResponse;

    /**
     * Create a product in a sub-subcategory (Admin only)
     * Products can only be assigned to level 2 categories (sub-subcategories)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sub_category_id' => [
                'required',
                'uuid',
                Rule::exists('categories', 'id')->where(function ($query) {
                    // Only allow level 2 categories (sub-subcategories)
                    $query->where('level', 2);
                }),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'image_url' => 'nullable|url|max:255',
        ], [
            'sub_category_id.exists' => 'Products can only be assigned to sub-subcategories (level 2).',
        ]);

        $product = Product::create($validated);

        return $this->success($product->load(['subcategory.parent.parent']), 'Product created successfully', 201);
    }

    /**
     * List products with full 3-level category hierarchy (Public)
     */
    public function index(Request $request)
    {
        $query = Product::with(['subcategory.parent.parent']);

        // Filter by top-level category (level 0)
        if ($request->has('category_id')) {
            $query->whereHas('subcategory.parent', function ($q) use ($request) {
                $q->where('parent_id', $request->category_id);
            });
        }

        // Filter by subcategory (level 1)
        if ($request->has('sub_category_id')) {
            $query->whereHas('subcategory', function ($q) use ($request) {
                $q->where('parent_id', $request->sub_category_id);
            });
        }

        // Filter by sub-subcategory (level 2) - direct product category
        if ($request->has('sub_sub_category_id')) {
            $query->where('sub_category_id', $request->sub_sub_category_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        return $this->success($products, 'Products fetched successfully');
    }

    /**
     * Update a product (Admin only)
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'sub_category_id' => [
                'sometimes',
                'required',
                'uuid',
                Rule::exists('categories', 'id')->where(function ($query) {
                    // Only allow level 2 categories (sub-subcategories)
                    $query->where('level', 2);
                }),
            ],
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'image_url' => 'nullable|url|max:255',
        ], [
            'sub_category_id.exists' => 'Products can only be assigned to sub-subcategories (level 2).',
        ]);

        $product->update($validated);

        return $this->success($product->fresh()->load(['subcategory.parent.parent']), 'Product updated successfully');
    }

    /**
     * Delete a product (Admin only)
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);

        // Check if product is in any cart or order
        if ($product->cartItems()->count() > 0) {
            return $this->error('Cannot delete product that is in user carts. Please remove from carts first.', 422);
        }

        // Note: We allow deletion even if in orders, as orders should preserve historical data
        $product->delete();

        return $this->success(null, 'Product deleted successfully');
    }
}
