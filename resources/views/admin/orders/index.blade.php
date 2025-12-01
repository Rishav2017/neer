@extends('layouts.admin')

@section('title', 'Orders')

@section('content')
<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-white">Orders</h1>
    </div>

    <!-- Filters -->
    <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-4 mb-6">
        <form method="GET" action="{{ route('admin.orders') }}" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search by order ID or user..."
                       class="w-full px-4 py-2 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <select name="status" class="px-4 py-2 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white focus:outline-none focus:border-blue-500">
                    <option value="">All Status</option>
                    @foreach(\App\Models\Order::STATUSES as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="payment_status" class="px-4 py-2 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white focus:outline-none focus:border-blue-500">
                    <option value="">All Payments</option>
                    <option value="pending" {{ request('payment_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="failed" {{ request('payment_status') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>
            <div>
                <select name="assigned" class="px-4 py-2 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white focus:outline-none focus:border-blue-500">
                    <option value="">All Orders</option>
                    <option value="0" {{ request('assigned') === '0' ? 'selected' : '' }}>Unassigned</option>
                    <option value="1" {{ request('assigned') === '1' ? 'selected' : '' }}>Assigned</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-[#3E3E3A] hover:bg-[#4a4a47] text-white rounded-lg transition-colors">
                Filter
            </button>
            @if(request()->hasAny(['search', 'status', 'payment_status', 'assigned']))
                <a href="{{ route('admin.orders') }}" class="px-4 py-2 text-gray-400 hover:text-white transition-colors">
                    Clear
                </a>
            @endif
        </form>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-4">
            <p class="text-sm text-gray-400">Total Orders</p>
            <p class="text-2xl font-bold text-white">{{ \App\Models\Order::count() }}</p>
        </div>
        <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-4">
            <p class="text-sm text-gray-400">Pending Assignment</p>
            <p class="text-2xl font-bold text-yellow-400">{{ \App\Models\Order::needsAssignment()->count() }}</p>
        </div>
        <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-4">
            <p class="text-sm text-gray-400">Out for Delivery</p>
            <p class="text-2xl font-bold text-blue-400">{{ \App\Models\Order::byStatus('out_for_delivery')->count() }}</p>
        </div>
        <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-4">
            <p class="text-sm text-gray-400">Delivered</p>
            <p class="text-2xl font-bold text-green-400">{{ \App\Models\Order::byStatus('delivered')->count() }}</p>
        </div>
        <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-4">
            <p class="text-sm text-gray-400">Cancelled</p>
            <p class="text-2xl font-bold text-red-400">{{ \App\Models\Order::byStatus('cancelled')->count() }}</p>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-[#3E3E3A]">
            <thead class="bg-[#1f1f1e]">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Order ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Payment</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Delivery Partner</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#3E3E3A]">
                @forelse($orders as $order)
                    <tr class="hover:bg-[#1f1f1e] transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="{{ route('admin.orders.show', $order) }}" class="text-sm text-blue-400 hover:text-blue-300 font-mono">
                                #{{ substr($order->id, 0, 8) }}...
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-white">{{ $order->user->name ?? 'N/A' }}</div>
                            <div class="text-xs text-gray-400">{{ $order->user->phone ?? '' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-white font-semibold">
                            ₹{{ number_format($order->total_amount ?? 0, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full
                                @if($order->status === 'delivered') bg-green-600/20 text-green-300 border border-green-600/30
                                @elseif($order->status === 'cancelled') bg-red-600/20 text-red-300 border border-red-600/30
                                @elseif($order->status === 'placed') bg-yellow-600/20 text-yellow-300 border border-yellow-600/30
                                @elseif($order->status === 'out_for_delivery') bg-purple-600/20 text-purple-300 border border-purple-600/30
                                @else bg-blue-600/20 text-blue-300 border border-blue-600/30
                                @endif">
                                {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full
                                @if($order->payment_status === 'paid') bg-green-600/20 text-green-300
                                @elseif($order->payment_status === 'failed') bg-red-600/20 text-red-300
                                @else bg-gray-600/20 text-gray-300
                                @endif">
                                {{ ucfirst($order->payment_status ?? 'pending') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($order->deliveryPartner)
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-green-600/20 flex items-center justify-center mr-2">
                                        <span class="text-green-300 font-semibold text-xs">
                                            {{ strtoupper(substr($order->deliveryPartner->name, 0, 2)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="text-sm text-white">{{ $order->deliveryPartner->name }}</div>
                                        <div class="text-xs text-gray-400">{{ $order->deliveryPartner->phone }}</div>
                                    </div>
                                </div>
                            @else
                                @if($order->canBeAssigned())
                                    <button onclick="openAssignModal('{{ $order->id }}')"
                                            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                        Assign
                                    </button>
                                @else
                                    <span class="text-gray-500 text-sm">-</span>
                                @endif
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                            {{ $order->created_at->format('M d, H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <div class="flex items-center justify-end space-x-2">
                                <!-- View -->
                                <a href="{{ route('admin.orders.show', $order) }}" class="p-2 text-blue-400 hover:text-blue-300 transition-colors" title="View Details">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>

                                @if($order->deliveryPartner && $order->canBeAssigned())
                                    <!-- Unassign -->
                                    <form method="POST" action="{{ route('admin.orders.unassign', $order) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-yellow-400 hover:text-yellow-300 transition-colors" title="Unassign Partner">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-gray-400">
                            No orders found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($orders->hasPages())
            <div class="px-6 py-4 border-t border-[#3E3E3A]">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Assign Partner Modal -->
<div id="assignModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center">
    <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-6 w-full max-w-md mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-white">Assign Delivery Partner</h3>
            <button onclick="closeAssignModal()" class="text-gray-400 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="assignForm" method="POST" action="">
            @csrf

            @if($availablePartners->count() > 0)
                <div class="space-y-3 max-h-64 overflow-y-auto">
                    @foreach($availablePartners as $partner)
                        <label class="flex items-center p-3 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg cursor-pointer hover:border-blue-500 transition-colors">
                            <input type="radio" name="delivery_partner_id" value="{{ $partner->id }}" class="mr-3 text-blue-600">
                            <div class="flex items-center flex-1">
                                <div class="w-10 h-10 rounded-full bg-[#3E3E3A] flex items-center justify-center mr-3">
                                    <span class="text-white font-semibold text-sm">
                                        {{ strtoupper(substr($partner->name, 0, 2)) }}
                                    </span>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-white">{{ $partner->name }}</div>
                                    <div class="text-xs text-gray-400">{{ $partner->phone }}</div>
                                </div>
                            </div>
                            <span class="px-2 py-1 text-xs rounded-full bg-green-600/20 text-green-300">Available</span>
                        </label>
                    @endforeach
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeAssignModal()" class="px-4 py-2 text-gray-400 hover:text-white transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                        Assign Partner
                    </button>
                </div>
            @else
                <div class="text-center py-8">
                    <svg class="w-12 h-12 text-gray-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <p class="text-gray-400">No delivery partners available right now.</p>
                    <a href="{{ route('admin.delivery-partners.create') }}" class="text-blue-400 hover:text-blue-300 text-sm mt-2 inline-block">
                        Add a new partner
                    </a>
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="button" onclick="closeAssignModal()" class="px-4 py-2 text-gray-400 hover:text-white transition-colors">
                        Close
                    </button>
                </div>
            @endif
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openAssignModal(orderId) {
        const modal = document.getElementById('assignModal');
        const form = document.getElementById('assignForm');
        form.action = `/admin/orders/${orderId}/assign`;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeAssignModal() {
        const modal = document.getElementById('assignModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Close modal on outside click
    document.getElementById('assignModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeAssignModal();
        }
    });

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAssignModal();
        }
    });
</script>
@endpush
@endsection
