<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Show the admin dashboard with analytics
     */
    public function index()
    {
        $today = Carbon::today();
        $lastHour = Carbon::now()->subHour();

        // Basic counts
        $stats = [
            'users' => [
                'total' => User::count(),
                'today' => User::whereDate('created_at', $today)->count(),
            ],
            'orders' => [
                'total' => Order::count(),
                'today' => Order::whereDate('created_at', $today)->count(),
                'by_status' => Order::selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray(),
            ],
            'products' => [
                'total' => Product::count(),
            ],
            'categories' => [
                'total' => Category::count(),
            ],
        ];

        // Revenue statistics (check if Payment model exists)
        $stats['revenue'] = [
            'total' => 0,
            'today' => 0,
        ];

        if (class_exists(Payment::class)) {
            try {
                $stats['revenue']['total'] = (float) Payment::where('status', 'paid')->sum('amount');
                $stats['revenue']['today'] = (float) Payment::where('status', 'paid')
                    ->whereDate('paid_at', $today)
                    ->sum('amount');
            } catch (\Exception $e) {
                // Payment table might not exist yet
            }
        }

        // Delivery partner statistics (check if model exists)
        $stats['delivery_partners'] = [
            'total' => 0,
            'active' => 0,
            'available' => 0,
        ];

        if (class_exists(DeliveryPartner::class)) {
            try {
                $stats['delivery_partners']['total'] = DeliveryPartner::count();
                $stats['delivery_partners']['active'] = DeliveryPartner::where('is_active', true)->count();
                $stats['delivery_partners']['available'] = DeliveryPartner::where('is_active', true)
                    ->where('is_available', true)->count();
            } catch (\Exception $e) {
                // DeliveryPartner table might not exist yet
            }
        }

        // Payment statistics
        $stats['payments'] = [
            'pending' => ['count' => 0, 'amount' => 0],
            'successful' => ['count' => 0, 'amount' => 0],
            'failed' => ['count' => 0],
        ];

        if (class_exists(Payment::class)) {
            try {
                $stats['payments']['pending']['count'] = Payment::where('status', 'pending')->count();
                $stats['payments']['pending']['amount'] = (float) Payment::where('status', 'pending')->sum('amount');
                $stats['payments']['successful']['count'] = Payment::where('status', 'paid')->count();
                $stats['payments']['successful']['amount'] = (float) Payment::where('status', 'paid')->sum('amount');
                $stats['payments']['failed']['count'] = Payment::where('status', 'failed')->count();
            } catch (\Exception $e) {
                // Payment table might not exist yet
            }
        }

        // Real-time stats
        $stats['realtime'] = [
            'orders_last_hour' => Order::where('created_at', '>=', $lastHour)->count(),
            'active_orders' => Order::whereNotIn('status', ['delivered', 'cancelled'])->count(),
        ];

        // Recent orders
        $recentOrders = Order::with('user')
            ->latest()
            ->take(10)
            ->get();

        // Daily orders for last 7 days (for chart)
        $dailyOrders = Order::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereDate('created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Daily revenue for last 7 days (for chart)
        $dailyRevenue = [];
        if (class_exists(Payment::class)) {
            try {
                $dailyRevenue = Payment::selectRaw('DATE(paid_at) as date, SUM(amount) as total')
                    ->where('status', 'paid')
                    ->whereDate('paid_at', '>=', Carbon::now()->subDays(7))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
            } catch (\Exception $e) {
                $dailyRevenue = collect([]);
            }
        }

        return view('admin.dashboard', compact('stats', 'recentOrders', 'dailyOrders', 'dailyRevenue'));
    }
}
