@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-white">Dashboard</h1>
        <div class="text-sm text-gray-400">
            Last updated: {{ now()->format('M d, Y H:i') }}
        </div>
    </div>

    <!-- Real-time Stats Banner -->
    <div class="bg-gradient-to-r from-blue-900/40 to-purple-900/40 border border-blue-800/50 rounded-lg p-4 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-6">
                <div class="flex items-center">
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse mr-2"></div>
                    <span class="text-sm text-gray-300">Live</span>
                </div>
                <div>
                    <span class="text-gray-400 text-sm">Orders (last hour):</span>
                    <span class="text-white font-semibold ml-1">{{ $stats['realtime']['orders_last_hour'] }}</span>
                </div>
                <div>
                    <span class="text-gray-400 text-sm">Active Orders:</span>
                    <span class="text-white font-semibold ml-1">{{ $stats['realtime']['active_orders'] }}</span>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-gray-400 text-sm">Delivery Partners Available:</span>
                <span class="text-green-400 font-semibold">{{ $stats['delivery_partners']['available'] }}</span>
            </div>
        </div>
    </div>

    <!-- Main Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Users -->
        <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">Total Users</p>
                    <p class="text-3xl font-bold text-white">{{ number_format($stats['users']['total']) }}</p>
                    <p class="text-xs text-green-400 mt-1">+{{ $stats['users']['today'] }} today</p>
                </div>
                <div class="w-12 h-12 bg-blue-600/20 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Orders -->
        <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">Total Orders</p>
                    <p class="text-3xl font-bold text-white">{{ number_format($stats['orders']['total']) }}</p>
                    <p class="text-xs text-green-400 mt-1">+{{ $stats['orders']['today'] }} today</p>
                </div>
                <div class="w-12 h-12 bg-green-600/20 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Revenue -->
        <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">Total Revenue</p>
                    <p class="text-3xl font-bold text-white">₹{{ number_format($stats['revenue']['total'], 2) }}</p>
                    <p class="text-xs text-green-400 mt-1">+₹{{ number_format($stats['revenue']['today'], 2) }} today</p>
                </div>
                <div class="w-12 h-12 bg-yellow-600/20 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Products -->
        <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">Products</p>
                    <p class="text-3xl font-bold text-white">{{ number_format($stats['products']['total']) }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $stats['categories']['total'] }} categories</p>
                </div>
                <div class="w-12 h-12 bg-purple-600/20 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Second Row Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Delivery Partners -->
        <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Delivery Partners</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-400">Total</span>
                    <span class="text-white font-semibold">{{ $stats['delivery_partners']['total'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-400">Active</span>
                    <span class="text-green-400 font-semibold">{{ $stats['delivery_partners']['active'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-400">Available Now</span>
                    <span class="text-blue-400 font-semibold">{{ $stats['delivery_partners']['available'] }}</span>
                </div>
            </div>
        </div>

        <!-- Payment Stats -->
        <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Payment Statistics</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-400">Successful</span>
                    <div class="text-right">
                        <span class="text-green-400 font-semibold">{{ $stats['payments']['successful']['count'] }}</span>
                        <span class="text-gray-500 text-sm ml-1">(₹{{ number_format($stats['payments']['successful']['amount'], 0) }})</span>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-400">Pending</span>
                    <div class="text-right">
                        <span class="text-yellow-400 font-semibold">{{ $stats['payments']['pending']['count'] }}</span>
                        <span class="text-gray-500 text-sm ml-1">(₹{{ number_format($stats['payments']['pending']['amount'], 0) }})</span>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-400">Failed</span>
                    <span class="text-red-400 font-semibold">{{ $stats['payments']['failed']['count'] }}</span>
                </div>
            </div>
        </div>

        <!-- Order Status Distribution -->
        <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Orders by Status</h3>
            <div class="space-y-2">
                @forelse($stats['orders']['by_status'] as $status => $count)
                    <div class="flex justify-between items-center">
                        <span class="text-gray-400 capitalize">{{ str_replace('_', ' ', $status) }}</span>
                        <span class="px-2 py-1 text-xs rounded-full
                            @if($status === 'delivered') bg-green-600/20 text-green-300
                            @elseif($status === 'cancelled') bg-red-600/20 text-red-300
                            @elseif($status === 'pending') bg-yellow-600/20 text-yellow-300
                            @else bg-blue-600/20 text-blue-300
                            @endif">
                            {{ $count }}
                        </span>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">No orders yet</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Orders Chart -->
        <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Orders (Last 7 Days)</h3>
            <div class="h-48 flex items-end justify-between space-x-2">
                @php
                    $maxOrders = $dailyOrders->max('count') ?: 1;
                @endphp
                @foreach($dailyOrders as $day)
                    <div class="flex-1 flex flex-col items-center">
                        <div class="w-full bg-blue-600/60 rounded-t transition-all hover:bg-blue-500/80"
                             style="height: {{ ($day->count / $maxOrders) * 100 }}%"
                             title="{{ $day->count }} orders">
                        </div>
                        <span class="text-xs text-gray-500 mt-2">{{ \Carbon\Carbon::parse($day->date)->format('D') }}</span>
                    </div>
                @endforeach
                @if($dailyOrders->isEmpty())
                    <div class="w-full text-center text-gray-500">No data available</div>
                @endif
            </div>
        </div>

        <!-- Revenue Chart -->
        <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Revenue (Last 7 Days)</h3>
            <div class="h-48 flex items-end justify-between space-x-2">
                @php
                    $maxRevenue = $dailyRevenue instanceof \Illuminate\Support\Collection ? ($dailyRevenue->max('total') ?: 1) : 1;
                @endphp
                @if($dailyRevenue instanceof \Illuminate\Support\Collection && $dailyRevenue->isNotEmpty())
                    @foreach($dailyRevenue as $day)
                        <div class="flex-1 flex flex-col items-center">
                            <div class="w-full bg-green-600/60 rounded-t transition-all hover:bg-green-500/80"
                                 style="height: {{ ($day->total / $maxRevenue) * 100 }}%"
                                 title="₹{{ number_format($day->total, 0) }}">
                            </div>
                            <span class="text-xs text-gray-500 mt-2">{{ \Carbon\Carbon::parse($day->date)->format('D') }}</span>
                        </div>
                    @endforeach
                @else
                    <div class="w-full text-center text-gray-500">No data available</div>
                @endif
            </div>
        </div>
    </div>

    <!-- Recent Orders Table -->
    <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-white">Recent Orders</h2>
            <a href="{{ route('admin.orders') }}" class="text-sm text-blue-400 hover:text-blue-300">View all →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[#3E3E3A]">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Order ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Payment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#3E3E3A]">
                    @forelse($recentOrders as $order)
                        <tr class="hover:bg-[#1f1f1e] transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-white font-mono">
                                #{{ substr($order->id, 0, 8) }}...
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                {{ $order->user->name ?? $order->user->phone ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-white">
                                ₹{{ number_format($order->total_amount ?? 0, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full
                                    @if($order->status === 'delivered') bg-green-600/20 text-green-300 border border-green-600/30
                                    @elseif($order->status === 'cancelled') bg-red-600/20 text-red-300 border border-red-600/30
                                    @elseif($order->status === 'pending') bg-yellow-600/20 text-yellow-300 border border-yellow-600/30
                                    @elseif($order->status === 'out_for_delivery') bg-purple-600/20 text-purple-300 border border-purple-600/30
                                    @else bg-blue-600/20 text-blue-300 border border-blue-600/30
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $order->status ?? 'Pending')) }}
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                {{ $order->created_at->format('M d, Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-400">
                                No orders found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

