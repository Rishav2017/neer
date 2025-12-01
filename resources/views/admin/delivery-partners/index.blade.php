@extends('layouts.admin')

@section('title', 'Delivery Partners')

@section('content')
<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-white">Delivery Partners</h1>
        <a href="{{ route('admin.delivery-partners.create') }}"
           class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
            + Add Partner
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-4 mb-6">
        <form method="GET" action="{{ route('admin.delivery-partners.index') }}" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search by name or phone..."
                       class="w-full px-4 py-2 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <select name="status" class="px-4 py-2 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white focus:outline-none focus:border-blue-500">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div>
                <select name="available" class="px-4 py-2 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white focus:outline-none focus:border-blue-500">
                    <option value="">All Availability</option>
                    <option value="1" {{ request('available') === '1' ? 'selected' : '' }}>Available</option>
                    <option value="0" {{ request('available') === '0' ? 'selected' : '' }}>On Delivery</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-[#3E3E3A] hover:bg-[#4a4a47] text-white rounded-lg transition-colors">
                Filter
            </button>
            @if(request()->hasAny(['search', 'status', 'available']))
                <a href="{{ route('admin.delivery-partners.index') }}" class="px-4 py-2 text-gray-400 hover:text-white transition-colors">
                    Clear
                </a>
            @endif
        </form>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-4">
            <p class="text-sm text-gray-400">Total Partners</p>
            <p class="text-2xl font-bold text-white">{{ \App\Models\DeliveryPartner::count() }}</p>
        </div>
        <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-4">
            <p class="text-sm text-gray-400">Active</p>
            <p class="text-2xl font-bold text-green-400">{{ \App\Models\DeliveryPartner::where('status', 'active')->count() }}</p>
        </div>
        <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-4">
            <p class="text-sm text-gray-400">Available Now</p>
            <p class="text-2xl font-bold text-blue-400">{{ \App\Models\DeliveryPartner::available()->count() }}</p>
        </div>
        <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-4">
            <p class="text-sm text-gray-400">On Delivery</p>
            <p class="text-2xl font-bold text-yellow-400">{{ \App\Models\DeliveryPartner::where('status', 'active')->whereNotNull('current_order_id')->count() }}</p>
        </div>
    </div>

    <!-- Partners Table -->
    <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-[#3E3E3A]">
            <thead class="bg-[#1f1f1e]">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Partner</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Phone</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Current Order</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Location</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Joined</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#3E3E3A]">
                @forelse($partners as $partner)
                    <tr class="hover:bg-[#1f1f1e] transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-[#3E3E3A] flex items-center justify-center mr-3">
                                    <span class="text-white font-semibold text-sm">
                                        {{ strtoupper(substr($partner->name, 0, 2)) }}
                                    </span>
                                </div>
                                <div class="text-sm font-medium text-white">{{ $partner->name }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                            {{ $partner->phone }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full
                                {{ $partner->status === 'active' ? 'bg-green-600/20 text-green-300 border border-green-600/30' : 'bg-red-600/20 text-red-300 border border-red-600/30' }}">
                                {{ ucfirst($partner->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($partner->current_order_id)
                                <span class="px-2 py-1 text-xs rounded-full bg-yellow-600/20 text-yellow-300 border border-yellow-600/30">
                                    #{{ substr($partner->current_order_id, 0, 8) }}...
                                </span>
                            @else
                                <span class="text-gray-500 text-sm">Available</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                            @if($partner->location_lat && $partner->location_lng)
                                {{ number_format($partner->location_lat, 4) }}, {{ number_format($partner->location_lng, 4) }}
                            @else
                                <span class="text-gray-500">Not set</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                            {{ $partner->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <div class="flex items-center justify-end space-x-2">
                                <!-- Toggle Status -->
                                <form method="POST" action="{{ route('admin.delivery-partners.toggle-status', $partner) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="p-2 text-gray-400 hover:text-white transition-colors" title="Toggle Status">
                                        @if($partner->status === 'active')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        @endif
                                    </button>
                                </form>

                                <!-- Clear Assignment -->
                                @if($partner->current_order_id)
                                    <form method="POST" action="{{ route('admin.delivery-partners.clear-assignment', $partner) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="p-2 text-yellow-400 hover:text-yellow-300 transition-colors" title="Clear Assignment">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif

                                <!-- Edit -->
                                <a href="{{ route('admin.delivery-partners.edit', $partner) }}" class="p-2 text-blue-400 hover:text-blue-300 transition-colors" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>

                                <!-- Delete -->
                                <form method="POST" action="{{ route('admin.delivery-partners.destroy', $partner) }}" class="inline"
                                      onsubmit="return confirm('Are you sure you want to delete this partner?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-red-400 hover:text-red-300 transition-colors" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                            No delivery partners found.
                            <a href="{{ route('admin.delivery-partners.create') }}" class="text-blue-400 hover:text-blue-300 ml-1">Add one now</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($partners->hasPages())
            <div class="px-6 py-4 border-t border-[#3E3E3A]">
                {{ $partners->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
