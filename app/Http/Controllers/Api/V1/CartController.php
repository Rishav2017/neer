<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    use ApiResponse;

    /**
     * Add product to cart (User only)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|uuid|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        // Check stock availability
        if (!$product->isInStock($validated['quantity'])) {
            return $this->error('Insufficient stock available', 422);
        }

        $user = $request->user();

        // Check if item already exists in cart
        $cartItem = CartItem::where('user_id', $user->id)
            ->where('product_id', $validated['product_id'])
            ->first();

        if ($cartItem) {
            // Update quantity
            $newQuantity = $cartItem->quantity + $validated['quantity'];

            if (!$product->isInStock($newQuantity)) {
                return $this->error('Insufficient stock available for the requested quantity', 422);
            }

            $cartItem->update(['quantity' => $newQuantity]);
        } else {
            // Create new cart item
            $cartItem = CartItem::create([
                'user_id' => $user->id,
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
            ]);
        }

        return $this->success($cartItem->load('product'), 'Product added to cart successfully', 201);
    }

    /**
     * List cart items (User only)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $cartItems = CartItem::with('product.subcategory.parent')
            ->where('user_id', $user->id)
            ->get();

        $total = $cartItems->sum(function ($item) {
            return $item->quantity * $item->product->price;
        });

        return $this->success([
            'items' => $cartItems,
            'total' => $total,
        ], 'Cart items fetched successfully');
    }

    /**
     * Update cart item quantity (User only)
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user();

        $cartItem = CartItem::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $product = $cartItem->product;

        // Check stock availability
        if (!$product->isInStock($validated['quantity'])) {
            return $this->error('Insufficient stock available', 422);
        }

        $cartItem->update(['quantity' => $validated['quantity']]);

        return $this->success($cartItem->load('product'), 'Cart item updated successfully');
    }

    /**
     * Remove product from cart (User only)
     */
    public function destroy(Request $request, string $id)
    {
        $user = $request->user();

        $cartItem = CartItem::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $cartItem->delete();

        return $this->success(null, 'Product removed from cart successfully');
    }
}
