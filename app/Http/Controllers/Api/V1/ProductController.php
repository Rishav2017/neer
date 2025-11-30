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
     * Create a product in a subcategory (Admin only)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sub_category_id' => [
                'required',
                'uuid',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->whereNotNull('parent_id');
                }),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'image_url' => 'nullable|url|max:255',
        ]);

        $product = Product::create($validated);

        return $this->success($product->load(['subcategory.parent']), 'Product created successfully', 201);
    }

    /**
     * List products with category and subcategory (Public)
     */
    public function index(Request $request)
    {
        $query = Product::with(['subcategory.parent']);

        // Optional filters
        if ($request->has('category_id')) {
            $query->whereHas('subcategory', function ($q) use ($request) {
                $q->where('parent_id', $request->category_id);
            });
        }

        if ($request->has('sub_category_id')) {
            $query->where('sub_category_id', $request->sub_category_id);
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
                    $query->whereNotNull('parent_id');
                }),
            ],
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'image_url' => 'nullable|url|max:255',
        ]);

        $product->update($validated);

        return $this->success($product->fresh()->load(['subcategory.parent']), 'Product updated successfully');
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
