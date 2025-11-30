<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    use ApiResponse;

    /**
     * Place an order from cart (User only)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'delivery_address' => 'required|string',
        ]);

        $user = $request->user();

        // Get user's cart items
        $cartItems = CartItem::with('product')
            ->where('user_id', $user->id)
            ->get();

        if ($cartItems->isEmpty()) {
            return $this->error('Cart is empty. Please add items to cart before placing an order.', 422);
        }

        // Validate stock and calculate total
        $totalAmount = 0;
        $orderItemsData = [];

        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;

            // Check stock availability
            if (!$product->isInStock($cartItem->quantity)) {
                return $this->error("Insufficient stock for product: {$product->name}", 422);
            }

            $itemTotal = $cartItem->quantity * $product->price;
            $totalAmount += $itemTotal;

            $orderItemsData[] = [
                'product_id' => $product->id,
                'quantity' => $cartItem->quantity,
                'price' => $product->price,
            ];
        }

        // Create order and order items in a transaction
        DB::beginTransaction();
        try {
            $order = Order::create([
                'user_id' => $user->id,
                'status' => 'placed',
                'total_amount' => $totalAmount,
                'delivery_address' => $validated['delivery_address'],
            ]);

            foreach ($orderItemsData as $itemData) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'price' => $itemData['price'],
                ]);

                // Update product stock
                $product = Product::find($itemData['product_id']);
                $product->decrement('stock_quantity', $itemData['quantity']);
            }

            // Clear cart
            CartItem::where('user_id', $user->id)->delete();

            DB::commit();

            return $this->success($order->load('orderItems.product'), 'Order placed successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to place order. Please try again.', 500);
        }
    }

    /**
     * List user orders (User only)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $orders = Order::with('orderItems.product')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($request->get('per_page', 15));

        return $this->success($orders, 'Orders fetched successfully');
    }

    /**
     * View single order (User only)
     */
    public function show(Request $request, string $id)
    {
        $user = $request->user();

        $order = Order::with('orderItems.product.subcategory.parent')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return $this->success($order, 'Order fetched successfully');
    }
}
