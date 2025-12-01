<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\DeliveryPartner;
use App\Services\OrderAssignmentService;
use App\Traits\ApiResponse;
use App\Exceptions\InvalidOrderStatusTransitionException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderManagementController extends Controller
{
    use ApiResponse;

    /**
     * The order assignment service
     */
    protected OrderAssignmentService $assignmentService;

    /**
     * Create a new controller instance
     */
    public function __construct(OrderAssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    /**
     * List all orders with filters
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'deliveryPartner', 'address', 'orderItems.product']);

        // Filter by status
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        // Filter orders needing assignment
        if ($request->boolean('needs_assignment')) {
            $query->needsAssignment();
        }

        // Filter active orders only
        if ($request->boolean('active')) {
            $query->active();
        }

        // Filter by delivery partner
        if ($request->filled('delivery_partner_id')) {
            $query->where('delivery_partner_id', $request->delivery_partner_id);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Search by order ID
        if ($request->filled('search')) {
            $query->where('id', 'like', "%{$request->search}%");
        }

        $orders = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 15));

        return $this->success($orders, 'Orders retrieved successfully');
    }

    /**
     * Show a specific order
     */
    public function show(Order $order)
    {
        $order->load(['user', 'deliveryPartner', 'address', 'orderItems.product']);

        $orderData = $order->toArray();
        $orderData['allowed_transitions'] = $order->getAllowedTransitions();
        $orderData['can_be_assigned'] = $order->canBeAssigned();

        return $this->success($orderData, 'Order retrieved successfully');
    }

    /**
     * Assign a delivery partner to an order
     */
    public function assignPartner(Request $request, Order $order)
    {
        $validated = $request->validate([
            'delivery_partner_id' => 'required|exists:delivery_partners,id',
        ]);

        $partner = DeliveryPartner::findOrFail($validated['delivery_partner_id']);

        try {
            $order = $this->assignmentService->assignManually($order, $partner);

            $orderData = $order->toArray();
            $orderData['allowed_transitions'] = $order->getAllowedTransitions();

            return $this->success($orderData, 'Delivery partner assigned successfully');
        } catch (InvalidOrderStatusTransitionException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Unassign the delivery partner from an order
     */
    public function unassignPartner(Order $order)
    {
        try {
            $order = $this->assignmentService->unassign($order);

            $orderData = $order->toArray();
            $orderData['allowed_transitions'] = $order->getAllowedTransitions();

            return $this->success($orderData, 'Delivery partner unassigned successfully');
        } catch (InvalidOrderStatusTransitionException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(Order::STATUSES)],
        ]);

        try {
            $order = $this->assignmentService->updateStatus($order, $validated['status']);

            return $this->success([
                'order' => $order,
                'allowed_transitions' => $order->getAllowedTransitions(),
            ], 'Order status updated successfully');
        } catch (InvalidOrderStatusTransitionException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Get orders statistics
     */
    public function statistics()
    {
        $stats = [
            'total' => Order::count(),
            'by_status' => [
                'placed' => Order::byStatus(Order::STATUS_PLACED)->count(),
                'accepted' => Order::byStatus(Order::STATUS_ACCEPTED)->count(),
                'out_for_delivery' => Order::byStatus(Order::STATUS_OUT_FOR_DELIVERY)->count(),
                'delivered' => Order::byStatus(Order::STATUS_DELIVERED)->count(),
                'cancelled' => Order::byStatus(Order::STATUS_CANCELLED)->count(),
            ],
            'needs_assignment' => Order::needsAssignment()->count(),
            'active' => Order::active()->count(),
        ];

        return $this->success($stats, 'Order statistics retrieved successfully');
    }

    /**
     * Get orders that need assignment
     */
    public function needsAssignment()
    {
        $orders = Order::needsAssignment()
            ->with(['user', 'address', 'orderItems.product'])
            ->orderBy('created_at')
            ->get();

        return $this->success($orders, 'Orders needing assignment retrieved successfully');
    }

    /**
     * Cancel an order
     */
    public function cancel(Request $request, Order $order)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $order = $this->assignmentService->cancel($order, $validated['reason'] ?? null);

            return $this->success($order, 'Order cancelled successfully');
        } catch (InvalidOrderStatusTransitionException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
