<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPartner;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeliveryPartnerController extends Controller
{
    use ApiResponse;

    /**
     * List all delivery partners
     */
    public function index(Request $request)
    {
        $query = DeliveryPartner::query();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter available partners only
        if ($request->boolean('available')) {
            $query->available();
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
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 15));

        return $this->success($partners, 'Delivery partners retrieved successfully');
    }

    /**
     * Store a new delivery partner
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:delivery_partners,phone',
            'status' => ['sometimes', Rule::in([DeliveryPartner::STATUS_ACTIVE, DeliveryPartner::STATUS_INACTIVE])],
            'location_lat' => 'nullable|numeric|between:-90,90',
            'location_lng' => 'nullable|numeric|between:-180,180',
        ]);

        $partner = DeliveryPartner::create($validated);

        return $this->success($partner, 'Delivery partner created successfully', 201);
    }

    /**
     * Show a specific delivery partner
     */
    public function show(DeliveryPartner $deliveryPartner)
    {
        $deliveryPartner->load(['activeOrder', 'orders' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return $this->success($deliveryPartner, 'Delivery partner retrieved successfully');
    }

    /**
     * Update a delivery partner
     */
    public function update(Request $request, DeliveryPartner $deliveryPartner)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('delivery_partners', 'phone')->ignore($deliveryPartner->id),
            ],
            'status' => ['sometimes', Rule::in([DeliveryPartner::STATUS_ACTIVE, DeliveryPartner::STATUS_INACTIVE])],
            'location_lat' => 'nullable|numeric|between:-90,90',
            'location_lng' => 'nullable|numeric|between:-180,180',
        ]);

        // If setting to inactive and has active order, prevent it
        if (isset($validated['status']) &&
            $validated['status'] === DeliveryPartner::STATUS_INACTIVE &&
            $deliveryPartner->current_order_id) {
            return $this->error(
                'Cannot deactivate partner with an active order. Reassign or complete the order first.',
                422
            );
        }

        $deliveryPartner->update($validated);

        return $this->success($deliveryPartner->fresh(), 'Delivery partner updated successfully');
    }

    /**
     * Delete (soft delete) a delivery partner
     */
    public function destroy(DeliveryPartner $deliveryPartner)
    {
        // Cannot delete if partner has active order
        if ($deliveryPartner->current_order_id) {
            return $this->error(
                'Cannot delete partner with an active order. Reassign or complete the order first.',
                422
            );
        }

        $deliveryPartner->delete();

        return $this->success(null, 'Delivery partner deleted successfully');
    }

    /**
     * Update partner location
     */
    public function updateLocation(Request $request, DeliveryPartner $deliveryPartner)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $deliveryPartner->updateLocation($validated['latitude'], $validated['longitude']);

        return $this->success($deliveryPartner->fresh(), 'Location updated successfully');
    }

    /**
     * Get available partners for assignment
     */
    public function available()
    {
        $partners = DeliveryPartner::available()
            ->orderBy('name')
            ->get();

        return $this->success($partners, 'Available delivery partners retrieved successfully');
    }
}
