<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\DeliveryPartner;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of orders
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'deliveryPartner', 'address']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by assignment
        if ($request->filled('assigned')) {
            if ($request->assigned === '1') {
                $query->whereNotNull('delivery_partner_id');
            } else {
                $query->whereNull('delivery_partner_id');
            }
        }

        // Search by order ID or user phone
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q2) use ($search) {
                      $q2->where('phone', 'like', "%{$search}%")
                         ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(15);

        // Get available delivery partners for assignment modal
        $availablePartners = DeliveryPartner::available()->orderBy('name')->get();

        return view('admin.orders.index', compact('orders', 'availablePartners'));
    }

    /**
     * Display the specified order
     */
    public function show(Order $order)
    {
        $order->load(['user', 'deliveryPartner', 'address', 'orderItems.product', 'payment']);
        $availablePartners = DeliveryPartner::available()->orderBy('name')->get();

        return view('admin.orders.show', compact('order', 'availablePartners'));
    }

    /**
     * Assign a delivery partner to an order
     */
    public function assignPartner(Request $request, Order $order)
    {
        $request->validate([
            'delivery_partner_id' => 'required|exists:delivery_partners,id',
        ]);

        $partner = DeliveryPartner::findOrFail($request->delivery_partner_id);

        try {
            $order->assignDeliveryPartner($partner);
            return redirect()->back()->with('success', "Order assigned to {$partner->name} successfully.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Unassign delivery partner from an order
     */
    public function unassignPartner(Order $order)
    {
        try {
            $partnerName = $order->deliveryPartner->name ?? 'Partner';
            $order->unassignDeliveryPartner();
            return redirect()->back()->with('success', "Order unassigned from {$partnerName}.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:' . implode(',', Order::STATUSES),
        ]);

        try {
            $order->transitionTo($request->status);
            return redirect()->back()->with('success', "Order status updated to {$request->status}.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
