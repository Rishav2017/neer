<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Display a listing of products with 3-level category filtering
     */
    public function index(Request $request)
    {
        $query = Product::with('subcategory.parent.parent');

        // Filter by top-level category (level 0)
        if ($request->filled('category_id')) {
            $query->whereHas('subcategory.parent', function ($q) use ($request) {
                $q->where('parent_id', $request->category_id);
            });
        }

        // Filter by subcategory (level 1)
        if ($request->filled('sub_category_id')) {
            $query->whereHas('subcategory', function ($q) use ($request) {
                $q->where('parent_id', $request->sub_category_id);
            });
        }

        // Filter by sub-subcategory (level 2) - direct product category
        if ($request->filled('sub_sub_category_id')) {
            $query->where('sub_category_id', $request->sub_sub_category_id);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(15);

        // Load 3-level category hierarchy for filters
        $categories = Category::where('level', 0)
            ->with(['subcategories.subcategories'])
            ->orderBy('name')
            ->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    /**
     * Show the form for creating a new product
     */
    public function create()
    {
        // Load full 3-level hierarchy for selection
        $categories = Category::where('level', 0)
            ->with(['subcategories.subcategories'])
            ->orderBy('name')
            ->get();

        return view('admin.products.create', compact('categories'));
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sub_category_id' => [
                'required',
                'uuid',
                Rule::exists('categories', 'id')->where(function ($query) {
                    // Only allow level 2 categories (sub-sub-categories)
                    $query->where('level', 2);
                }),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'image_url' => 'nullable|url|max:255',
        ], [
            'sub_category_id.exists' => 'Please select a valid sub-subcategory.',
        ]);

        Product::create($validated);

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
    }

    /**
     * Show the form for editing a product
     */
    public function edit(Product $product)
    {
        $product->load('subcategory.parent.parent');

        // Load full 3-level hierarchy for selection
        $categories = Category::where('level', 0)
            ->with(['subcategories.subcategories'])
            ->orderBy('name')
            ->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'sub_category_id' => [
                'required',
                'uuid',
                Rule::exists('categories', 'id')->where(function ($query) {
                    // Only allow level 2 categories (sub-sub-categories)
                    $query->where('level', 2);
                }),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'image_url' => 'nullable|url|max:255',
        ], [
            'sub_category_id.exists' => 'Please select a valid sub-subcategory.',
        ]);

        $product->update($validated);

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified product
     */
    public function destroy(Product $product)
    {
        // Check if product is in any carts
        if ($product->cartItems()->count() > 0) {
            return redirect()->route('admin.products.index')
                ->with('error', 'Cannot delete product that is in user carts.');
        }

        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
    }
}
