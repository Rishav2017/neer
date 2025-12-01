@extends('layouts.admin')

@section('title', 'Edit Delivery Partner')

@section('content')
<div class="max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('admin.delivery-partners.index') }}" class="text-gray-400 hover:text-white text-sm flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Delivery Partners
        </a>
    </div>

    <h1 class="text-3xl font-bold text-white mb-6">Edit Delivery Partner</h1>

    <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-6">
        <form method="POST" action="{{ route('admin.delivery-partners.update', $deliveryPartner) }}">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-300 mb-2">Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $deliveryPartner->name) }}"
                           class="w-full px-4 py-2 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 @error('name') border-red-500 @enderror"
                           placeholder="Enter partner name" required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-300 mb-2">Phone Number</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $deliveryPartner->phone) }}"
                           class="w-full px-4 py-2 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 @error('phone') border-red-500 @enderror"
                           placeholder="e.g., +91 9876543210" required>
                    @error('phone')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-300 mb-2">Status</label>
                    <select name="status" id="status"
                            class="w-full px-4 py-2 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white focus:outline-none focus:border-blue-500 @error('status') border-red-500 @enderror">
                        <option value="active" {{ old('status', $deliveryPartner->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $deliveryPartner->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Current Assignment Info (Read-only) -->
                @if($deliveryPartner->current_order_id)
                    <div class="p-4 bg-yellow-900/20 border border-yellow-800 rounded-lg">
                        <p class="text-sm text-yellow-300">
                            <strong>Currently assigned to order:</strong> #{{ substr($deliveryPartner->current_order_id, 0, 8) }}...
                        </p>
                    </div>
                @endif

                <!-- Location Info (Read-only) -->
                @if($deliveryPartner->location_lat && $deliveryPartner->location_lng)
                    <div class="p-4 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg">
                        <p class="text-sm text-gray-400">
                            <strong>Last Known Location:</strong>
                            {{ number_format($deliveryPartner->location_lat, 6) }}, {{ number_format($deliveryPartner->location_lng, 6) }}
                        </p>
                    </div>
                @endif
            </div>

            <div class="mt-8 flex items-center justify-end space-x-4">
                <a href="{{ route('admin.delivery-partners.index') }}"
                   class="px-4 py-2 text-gray-400 hover:text-white transition-colors">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                    Update Partner
                </button>
            </div>
        </form>
    </div>

    <!-- Danger Zone -->
    <div class="mt-6 bg-[#161615] border border-red-900/50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-red-400 mb-4">Danger Zone</h3>
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-300">Delete this delivery partner</p>
                <p class="text-xs text-gray-500">This action cannot be undone.</p>
            </div>
            <form method="POST" action="{{ route('admin.delivery-partners.destroy', $deliveryPartner) }}"
                  onsubmit="return confirm('Are you sure you want to delete this partner? This action cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors"
                        {{ $deliveryPartner->current_order_id ? 'disabled' : '' }}>
                    Delete Partner
                </button>
            </form>
        </div>
        @if($deliveryPartner->current_order_id)
            <p class="mt-2 text-xs text-yellow-400">Cannot delete while assigned to an order.</p>
        @endif
    </div>
</div>
@endsection
