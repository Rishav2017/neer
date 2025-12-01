@extends('layouts.admin')

@section('title', 'Order Details')

@section('content')
<div>
    <div class="mb-6">
        <a href="{{ route('admin.orders.index') }}" class="text-gray-400 hover:text-white text-sm flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Orders
        </a>
    </div>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-white">Order #{{ substr($order->id, 0, 8) }}...</h1>
            <p class="text-gray-400 text-sm mt-1">Created {{ $order->created_at->format('M d, Y \a\t H:i') }}</p>
        </div>
        <div class="flex items-center space-x-3">
            <span class="px-3 py-1.5 text-sm rounded-full
                @if($order->status === 'delivered') bg-green-600/20 text-green-300 border border-green-600/30
                @elseif($order->status === 'cancelled') bg-red-600/20 text-red-300 border border-red-600/30
                @elseif($order->status === 'placed') bg-yellow-600/20 text-yellow-300 border border-yellow-600/30
                @elseif($order->status === 'out_for_delivery') bg-purple-600/20 text-purple-300 border border-purple-600/30
                @else bg-blue-600/20 text-blue-300 border border-blue-600/30
                @endif">
                {{ ucfirst(str_replace('_', ' ', $order->status)) }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Order Items -->
            <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Order Items</h2>
                <div class="space-y-4">
                    @forelse($order->orderItems as $item)
                        <div class="flex items-center justify-between p-3 bg-[#0a0a0a] rounded-lg">
                            <div class="flex items-center">
                                @if($item->product && $item->product->image_url)
                                    <img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}"
                                         class="w-12 h-12 object-cover rounded-lg mr-4">
                                @else
                                    <div class="w-12 h-12 bg-[#3E3E3A] rounded-lg mr-4 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                    </div>
                                @endif
                                <div>
                                    <div class="text-white font-medium">{{ $item->product->name ?? 'Unknown Product' }}</div>
                                    <div class="text-gray-400 text-sm">Qty: {{ $item->quantity }} x ₹{{ number_format($item->price, 2) }}</div>
                                </div>
                            </div>
                            <div class="text-white font-semibold">
                                ₹{{ number_format($item->quantity * $item->price, 2) }}
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-400 text-center py-4">No items found</p>
                    @endforelse
                </div>

                <div class="mt-4 pt-4 border-t border-[#3E3E3A]">
                    <div class="flex justify-between text-lg font-semibold">
                        <span class="text-white">Total</span>
                        <span class="text-white">₹{{ number_format($order->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Delivery Address -->
            <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Delivery Address</h2>
                @if($order->address)
                    <div class="text-gray-300">
                        <p class="font-medium text-white">{{ $order->address->label ?? 'Address' }}</p>
                        <p>{{ $order->address->address_line_1 }}</p>
                        @if($order->address->address_line_2)
                            <p>{{ $order->address->address_line_2 }}</p>
                        @endif
                        <p>{{ $order->address->city }}, {{ $order->address->state }} {{ $order->address->postal_code }}</p>
                    </div>
                @elseif($order->delivery_address)
                    <p class="text-gray-300">{{ $order->delivery_address }}</p>
                @else
                    <p class="text-gray-400">No address provided</p>
                @endif
            </div>

            <!-- Status Update -->
            @if(!$order->isTerminal())
                <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-white mb-4">Update Status</h2>
                    <form method="POST" action="{{ route('admin.orders.update-status', $order) }}" class="flex items-center space-x-4">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="flex-1 px-4 py-2 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white focus:outline-none focus:border-blue-500">
                            @foreach($order->getAllowedTransitions() as $status)
                                <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                            Update
                        </button>
                    </form>
                    @if(empty($order->getAllowedTransitions()))
                        <p class="text-gray-400 text-sm mt-2">No status transitions available from current status.</p>
                    @endif
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Customer Info -->
            <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Customer</h2>
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 rounded-full bg-[#3E3E3A] flex items-center justify-center mr-3">
                        <span class="text-white font-semibold">
                            {{ strtoupper(substr($order->user->name ?? $order->user->phone ?? 'U', 0, 2)) }}
                        </span>
                    </div>
                    <div>
                        <div class="text-white font-medium">{{ $order->user->name ?? 'Unknown' }}</div>
                        <div class="text-gray-400 text-sm">{{ $order->user->phone ?? '' }}</div>
                    </div>
                </div>
                @if($order->user->email)
                    <p class="text-gray-400 text-sm">{{ $order->user->email }}</p>
                @endif
            </div>

            <!-- Payment Info -->
            <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Payment</h2>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Method</span>
                        <span class="text-white uppercase">{{ $order->payment_method ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Status</span>
                        <span class="px-2 py-1 text-xs rounded-full
                            @if($order->payment_status === 'paid') bg-green-600/20 text-green-300
                            @elseif($order->payment_status === 'failed') bg-red-600/20 text-red-300
                            @else bg-gray-600/20 text-gray-300
                            @endif">
                            {{ ucfirst($order->payment_status ?? 'pending') }}
                        </span>
                    </div>
                    @if($order->paid_at)
                        <div class="flex justify-between">
                            <span class="text-gray-400">Paid At</span>
                            <span class="text-white">{{ $order->paid_at->format('M d, H:i') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Delivery Partner -->
            <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Delivery Partner</h2>
                @if($order->deliveryPartner)
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 rounded-full bg-green-600/20 flex items-center justify-center mr-3">
                            <span class="text-green-300 font-semibold">
                                {{ strtoupper(substr($order->deliveryPartner->name, 0, 2)) }}
                            </span>
                        </div>
                        <div>
                            <div class="text-white font-medium">{{ $order->deliveryPartner->name }}</div>
                            <div class="text-gray-400 text-sm">{{ $order->deliveryPartner->phone }}</div>
                        </div>
                    </div>
                    @if($order->canBeAssigned())
                        <div class="flex space-x-2">
                            <button onclick="openAssignModal('{{ $order->id }}')"
                                    class="flex-1 px-3 py-2 bg-blue-600/20 text-blue-300 text-sm font-medium rounded-lg hover:bg-blue-600/30 transition-colors">
                                Reassign
                            </button>
                            <form method="POST" action="{{ route('admin.orders.unassign', $order) }}" class="flex-1">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full px-3 py-2 bg-red-600/20 text-red-300 text-sm font-medium rounded-lg hover:bg-red-600/30 transition-colors">
                                    Unassign
                                </button>
                            </form>
                        </div>
                    @endif
                @else
                    <p class="text-gray-400 mb-4">No delivery partner assigned</p>
                    @if($order->canBeAssigned())
                        <button onclick="openAssignModal('{{ $order->id }}')"
                                class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Assign Partner
                        </button>
                    @else
                        <p class="text-gray-500 text-sm">Cannot assign partner to this order</p>
                    @endif
                @endif
            </div>

            <!-- Order Notes -->
            @if($order->notes)
                <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-white mb-4">Notes</h2>
                    <p class="text-gray-300">{{ $order->notes }}</p>
                </div>
            @endif
        </div>
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

        <form id="assignForm" method="POST" action="{{ route('admin.orders.assign', $order) }}">
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
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeAssignModal() {
        const modal = document.getElementById('assignModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.getElementById('assignModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeAssignModal();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAssignModal();
        }
    });
</script>
@endpush
@endsection
