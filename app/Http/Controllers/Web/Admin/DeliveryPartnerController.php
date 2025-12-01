<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPartner;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeliveryPartnerController extends Controller
{
    /**
     * Display a listing of delivery partners
     */
    public function index(Request $request)
    {
        $query = DeliveryPartner::query();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by availability
        if ($request->filled('available')) {
            if ($request->available === '1') {
                $query->available();
            } else {
                $query->whereNotNull('current_order_id');
            }
        }

        // Search by name or phone
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $partners = $query->with('activeOrder')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.delivery-partners.index', compact('partners'));
    }

    /**
     * Show the form for creating a new delivery partner
     */
    public function create()
    {
        return view('admin.delivery-partners.create');
    }

    /**
     * Store a newly created delivery partner
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:delivery_partners,phone',
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        DeliveryPartner::create($validated);

        return redirect()->route('admin.delivery-partners.index')
            ->with('success', 'Delivery partner created successfully.');
    }

    /**
     * Display the specified delivery partner
     */
    public function show(DeliveryPartner $deliveryPartner)
    {
        $deliveryPartner->load(['orders' => function ($query) {
            $query->latest()->take(10);
        }, 'activeOrder']);

        return view('admin.delivery-partners.show', compact('deliveryPartner'));
    }

    /**
     * Show the form for editing a delivery partner
     */
    public function edit(DeliveryPartner $deliveryPartner)
    {
        return view('admin.delivery-partners.edit', compact('deliveryPartner'));
    }

    /**
     * Update the specified delivery partner
     */
    public function update(Request $request, DeliveryPartner $deliveryPartner)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'max:20', Rule::unique('delivery_partners', 'phone')->ignore($deliveryPartner->id)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $deliveryPartner->update($validated);

        return redirect()->route('admin.delivery-partners.index')
            ->with('success', 'Delivery partner updated successfully.');
    }

    /**
     * Remove the specified delivery partner
     */
    public function destroy(DeliveryPartner $deliveryPartner)
    {
        // Check if partner has an active order
        if ($deliveryPartner->current_order_id) {
            return redirect()->route('admin.delivery-partners.index')
                ->with('error', 'Cannot delete partner with an active order assignment.');
        }

        $deliveryPartner->delete();

        return redirect()->route('admin.delivery-partners.index')
            ->with('success', 'Delivery partner deleted successfully.');
    }

    /**
     * Toggle the status of a delivery partner
     */
    public function toggleStatus(DeliveryPartner $deliveryPartner)
    {
        $deliveryPartner->status = $deliveryPartner->status === 'active' ? 'inactive' : 'active';
        $deliveryPartner->save();

        return redirect()->back()
            ->with('success', 'Delivery partner status updated.');
    }

    /**
     * Clear the current order assignment
     */
    public function clearAssignment(DeliveryPartner $deliveryPartner)
    {
        $deliveryPartner->clearAssignment();

        return redirect()->back()
            ->with('success', 'Order assignment cleared.');
    }
}
