<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AddressController extends Controller
{
    use ApiResponse;

    /**
     * Get all addresses for the authenticated user
     */
    public function index(Request $request)
    {
        $addresses = $request->user()->addresses()
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();

        return $this->success($addresses, 'Addresses retrieved successfully');
    }

    /**
     * Store a new address for the authenticated user
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => ['required', Rule::in(['home', 'work', 'other'])],
            'address_line' => 'required|string',
            'area_name' => 'required|string|max:255',
            'landmark' => 'nullable|string|max:255',
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'pincode' => 'required|string|max:20',
            'is_default' => 'boolean',
        ]);

        $validated['user_id'] = $request->user()->id;

        $address = Address::create($validated);

        return $this->success($address, 'Address created successfully', 201);
    }

    /**
     * Get a specific address
     */
    public function show(Request $request, $id)
    {
        $address = $request->user()->addresses()->find($id);

        if (!$address) {
            return $this->error('Address not found', 404);
        }

        return $this->success($address, 'Address retrieved successfully');
    }

    /**
     * Update an existing address
     */
    public function update(Request $request, $id)
    {
        $address = $request->user()->addresses()->find($id);

        if (!$address) {
            return $this->error('Address not found', 404);
        }

        $validated = $request->validate([
            'label' => ['sometimes', 'required', Rule::in(['home', 'work', 'other'])],
            'address_line' => 'sometimes|required|string',
            'area_name' => 'sometimes|required|string|max:255',
            'landmark' => 'nullable|string|max:255',
            'receiver_name' => 'sometimes|required|string|max:255',
            'receiver_phone' => 'sometimes|required|string|max:20',
            'latitude' => 'sometimes|required|numeric|between:-90,90',
            'longitude' => 'sometimes|required|numeric|between:-180,180',
            'pincode' => 'sometimes|required|string|max:20',
            'is_default' => 'boolean',
        ]);

        $address->update($validated);

        return $this->success($address->fresh(), 'Address updated successfully');
    }

    /**
     * Delete an address
     */
    public function destroy(Request $request, $id)
    {
        $address = $request->user()->addresses()->find($id);

        if (!$address) {
            return $this->error('Address not found', 404);
        }

        $wasDefault = $address->is_default;
        $userId = $address->user_id;

        $address->delete();

        // If deleted address was default, make the most recent address the new default
        if ($wasDefault) {
            $newDefault = Address::where('user_id', $userId)
                ->orderByDesc('created_at')
                ->first();

            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }

        return $this->success(null, 'Address deleted successfully');
    }

    /**
     * Set an address as the default
     */
    public function setDefault(Request $request, $id)
    {
        $address = $request->user()->addresses()->find($id);

        if (!$address) {
            return $this->error('Address not found', 404);
        }

        $address->update(['is_default' => true]);

        return $this->success($address->fresh(), 'Default address updated successfully');
    }

    /**
     * Get the default address for the authenticated user
     */
    public function getDefault(Request $request)
    {
        $address = $request->user()->addresses()->default()->first();

        if (!$address) {
            return $this->error('No default address found', 404);
        }

        return $this->success($address, 'Default address retrieved successfully');
    }
}
