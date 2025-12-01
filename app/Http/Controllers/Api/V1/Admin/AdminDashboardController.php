<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    use ApiResponse;

    /**
     * Get dashboard statistics
     * GET /admin/stats
     */
    public function stats(): JsonResponse
    {
        $today = Carbon::today();

        // User statistics
        $totalUsers = User::count();
        $usersToday = User::whereDate('created_at', $today)->count();

        // Order statistics
        $totalOrders = Order::count();
        $ordersToday = Order::whereDate('created_at', $today)->count();
        $ordersByStatus = Order::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Revenue statistics
        $revenueTotal = Payment::paid()->sum('amount');
        $revenueToday = Payment::paid()
            ->whereDate('paid_at', $today)
            ->sum('amount');

        // Delivery partner statistics
        $totalDeliveryPartners = DeliveryPartner::count();
        $activeDeliveryPartners = DeliveryPartner::active()->count();
        $availableDeliveryPartners = DeliveryPartner::available()->count();

        // Payment statistics
        $pendingPayments = Payment::pending()->count();
        $successfulPayments = Payment::paid()->count();
        $failedPayments = Payment::failed()->count();

        // Payment totals
        $pendingPaymentsAmount = Payment::pending()->sum('amount');
        $successfulPaymentsAmount = Payment::paid()->sum('amount');

        return $this->success([
            'users' => [
                'total' => $totalUsers,
                'today' => $usersToday,
            ],
            'orders' => [
                'total' => $totalOrders,
                'today' => $ordersToday,
                'by_status' => $ordersByStatus,
            ],
            'revenue' => [
                'total' => (float) $revenueTotal,
                'today' => (float) $revenueToday,
                'currency' => 'INR',
            ],
            'delivery_partners' => [
                'total' => $totalDeliveryPartners,
                'active' => $activeDeliveryPartners,
                'available' => $availableDeliveryPartners,
            ],
            'payments' => [
                'pending' => [
                    'count' => $pendingPayments,
                    'amount' => (float) $pendingPaymentsAmount,
                ],
                'successful' => [
                    'count' => $successfulPayments,
                    'amount' => (float) $successfulPaymentsAmount,
                ],
                'failed' => [
                    'count' => $failedPayments,
                ],
            ],
        ], 'Dashboard statistics retrieved');
    }

    /**
     * Get detailed analytics with date range
     * GET /admin/analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        // Daily orders
        $dailyOrders = Order::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Daily revenue
        $dailyRevenue = Payment::selectRaw('DATE(paid_at) as date, SUM(amount) as total')
            ->paid()
            ->whereDate('paid_at', '>=', $startDate)
            ->whereDate('paid_at', '<=', $endDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Order status distribution
        $orderStatusDistribution = Order::selectRaw('status, COUNT(*) as count')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->groupBy('status')
            ->get();

        // Payment method distribution
        $paymentMethodDistribution = Payment::selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->groupBy('payment_method')
            ->get();

        // Top products (if order_items table exists)
        $topProducts = DB::table('order_items')
            ->select('product_id', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(price * quantity) as total_revenue'))
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereDate('orders.created_at', '>=', $startDate)
            ->whereDate('orders.created_at', '<=', $endDate)
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        return $this->success([
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'daily_orders' => $dailyOrders,
            'daily_revenue' => $dailyRevenue,
            'order_status_distribution' => $orderStatusDistribution,
            'payment_method_distribution' => $paymentMethodDistribution,
            'top_products' => $topProducts,
        ], 'Analytics retrieved');
    }

    /**
     * Get payment reports
     * GET /admin/reports/payments
     */
    public function paymentReports(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        // Payment summary
        $summary = Payment::selectRaw('
                status,
                payment_method,
                payment_gateway,
                COUNT(*) as count,
                SUM(amount) as total_amount
            ')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->groupBy('status', 'payment_method', 'payment_gateway')
            ->get();

        // Recent failed payments
        $failedPayments = Payment::failed()
            ->with(['user:id,name,phone', 'order:id,status,total_amount'])
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        // Pending payments over 24 hours
        $stalePendingPayments = Payment::pending()
            ->where('created_at', '<', Carbon::now()->subHours(24))
            ->with(['user:id,name,phone', 'order:id,status,total_amount'])
            ->limit(50)
            ->get();

        return $this->success([
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'summary' => $summary,
            'failed_payments' => $failedPayments,
            'stale_pending_payments' => $stalePendingPayments,
        ], 'Payment reports retrieved');
    }

    /**
     * Get real-time stats for live dashboard
     * GET /admin/stats/realtime
     */
    public function realtimeStats(): JsonResponse
    {
        $now = Carbon::now();
        $lastHour = $now->copy()->subHour();

        return $this->success([
            'orders_last_hour' => Order::where('created_at', '>=', $lastHour)->count(),
            'revenue_last_hour' => (float) Payment::paid()
                ->where('paid_at', '>=', $lastHour)
                ->sum('amount'),
            'active_orders' => Order::active()->count(),
            'orders_out_for_delivery' => Order::byStatus(Order::STATUS_OUT_FOR_DELIVERY)->count(),
            'available_delivery_partners' => DeliveryPartner::available()->count(),
            'pending_payments' => Payment::pending()->count(),
            'timestamp' => $now->toIso8601String(),
        ], 'Real-time stats retrieved');
    }
}
