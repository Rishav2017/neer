<?php

namespace App\Services;

use App\Models\Order;
use App\Models\DeliveryPartner;
use App\Events\OrderAssigned;
use App\Events\OrderUnassigned;
use App\Events\OrderStatusUpdated;
use App\Exceptions\InvalidOrderStatusTransitionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderAssignmentService
{
    /**
     * Auto-assignment mode flag
     * Set to true when ready to enable automatic assignment
     */
    protected bool $autoAssignmentEnabled = false;

    /**
     * Manually assign a delivery partner to an order
     *
     * @throws InvalidOrderStatusTransitionException
     */
    public function assignManually(Order $order, DeliveryPartner $partner): Order
    {
        $this->validateAssignment($order, $partner);

        return DB::transaction(function () use ($order, $partner) {
            $previousPartnerId = $order->delivery_partner_id;
            $previousStatus = $order->status;

            // Clear previous partner's assignment if exists
            if ($previousPartnerId && $order->deliveryPartner) {
                $order->deliveryPartner->clearAssignment();
            }

            // Assign partner to order
            $order->delivery_partner_id = $partner->id;
            $order->save();

            // Update partner's current order
            $partner->current_order_id = $order->id;
            $partner->save();

            // Auto-transition from placed to accepted
            if ($order->status === Order::STATUS_PLACED) {
                $order->status = Order::STATUS_ACCEPTED;
                $order->save();

                event(new OrderStatusUpdated($order, $previousStatus, Order::STATUS_ACCEPTED));
            }

            // Dispatch assignment event
            event(new OrderAssigned($order, $partner, 'manual'));

            // Reload relationships
            $order->load(['user', 'deliveryPartner', 'address', 'orderItems.product']);

            return $order;
        });
    }

    /**
     * Unassign the delivery partner from an order
     *
     * @throws InvalidOrderStatusTransitionException
     */
    public function unassign(Order $order): Order
    {
        $this->validateUnassignment($order);

        return DB::transaction(function () use ($order) {
            $previousStatus = $order->status;
            $previousPartner = $order->deliveryPartner;

            // Clear the partner's current_order_id
            if ($previousPartner) {
                $previousPartner->clearAssignment();
            }

            // Clear order's delivery_partner_id
            $order->delivery_partner_id = null;

            // Revert status if order was "accepted"
            if ($previousStatus === Order::STATUS_ACCEPTED) {
                $order->status = Order::STATUS_PLACED;
                event(new OrderStatusUpdated($order, $previousStatus, Order::STATUS_PLACED));
            }

            $order->save();

            // Dispatch unassignment event
            event(new OrderUnassigned($order, $previousPartner));

            // Reload relationships
            $order->load(['user', 'address', 'orderItems.product']);

            return $order;
        });
    }

    /**
     * Update order status with validation
     *
     * @throws InvalidOrderStatusTransitionException
     */
    public function updateStatus(Order $order, string $newStatus): Order
    {
        $previousStatus = $order->status;

        // Use the model's transition validation
        $order->transitionTo($newStatus);

        // Clear partner's current assignment if order is completed/cancelled
        if (in_array($newStatus, [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED])) {
            if ($order->deliveryPartner) {
                $order->deliveryPartner->clearAssignment();
            }
        }

        event(new OrderStatusUpdated($order, $previousStatus, $newStatus));

        $order->load('deliveryPartner');

        return $order;
    }

    /**
     * Cancel an order with optional reason
     *
     * @throws InvalidOrderStatusTransitionException
     */
    public function cancel(Order $order, ?string $reason = null): Order
    {
        if ($reason) {
            $order->notes = ($order->notes ? $order->notes . "\n" : '') . "Cancelled: " . $reason;
            $order->save();
        }

        return $this->updateStatus($order, Order::STATUS_CANCELLED);
    }

    /**
     * Validate assignment prerequisites
     *
     * @throws InvalidOrderStatusTransitionException
     */
    protected function validateAssignment(Order $order, DeliveryPartner $partner): void
    {
        // Check if order is in terminal state
        if ($order->isTerminal()) {
            throw new InvalidOrderStatusTransitionException(
                "Cannot assign partner to an order with status '{$order->status}'"
            );
        }

        // Prevent reassigning if already out for delivery
        if ($order->status === Order::STATUS_OUT_FOR_DELIVERY) {
            throw new InvalidOrderStatusTransitionException(
                'Cannot reassign partner once order is out for delivery'
            );
        }

        // Check if partner is active
        if (!$partner->isActive()) {
            throw new InvalidOrderStatusTransitionException(
                'Cannot assign inactive delivery partner'
            );
        }

        // Check if partner already has an active order
        if ($partner->current_order_id !== null && $partner->current_order_id !== $order->id) {
            throw new InvalidOrderStatusTransitionException(
                'Delivery partner already has an active order assigned'
            );
        }
    }

    /**
     * Validate unassignment prerequisites
     *
     * @throws InvalidOrderStatusTransitionException
     */
    protected function validateUnassignment(Order $order): void
    {
        if (!$order->delivery_partner_id) {
            throw new InvalidOrderStatusTransitionException(
                'Order has no assigned delivery partner'
            );
        }

        if ($order->isTerminal()) {
            throw new InvalidOrderStatusTransitionException(
                'Cannot unassign partner from a completed or cancelled order'
            );
        }

        if ($order->status === Order::STATUS_OUT_FOR_DELIVERY) {
            throw new InvalidOrderStatusTransitionException(
                'Cannot unassign partner once order is out for delivery'
            );
        }
    }

    // =========================================================================
    // FUTURE AUTO-ASSIGNMENT METHODS (MVP: Disabled)
    // =========================================================================

    /**
     * Automatically assign the best available partner to an order
     * Currently disabled for MVP - returns null
     *
     * @throws InvalidOrderStatusTransitionException
     */
    public function autoAssign(Order $order): ?Order
    {
        if (!$this->autoAssignmentEnabled) {
            Log::info('Auto-assignment is disabled', ['order_id' => $order->id]);
            return null;
        }

        $partner = $this->findBestPartner($order);

        if (!$partner) {
            Log::warning('No available partner found for auto-assignment', [
                'order_id' => $order->id,
            ]);
            return null;
        }

        return $this->assignManually($order, $partner);
    }

    /**
     * Find the best available delivery partner for an order
     * Placeholder for future implementation
     *
     * Criteria (to be implemented):
     * 1. Partner must be active and available
     * 2. Nearest to pickup location
     * 3. Load balancing (least orders assigned today)
     * 4. Partner rating/performance score
     */
    protected function findBestPartner(Order $order): ?DeliveryPartner
    {
        // Get order's delivery location
        $orderLat = $order->address?->latitude;
        $orderLng = $order->address?->longitude;

        // Get available partners
        $availablePartners = DeliveryPartner::available()->get();

        if ($availablePartners->isEmpty()) {
            return null;
        }

        // Placeholder: Return first available partner
        // TODO: Implement scoring algorithm
        return $this->selectPartnerByScore($availablePartners, $orderLat, $orderLng);
    }

    /**
     * Select best partner based on scoring algorithm
     * Placeholder implementation - returns first available
     *
     * Future scoring factors:
     * - Distance score (40%)
     * - Load balance score (30%)
     * - Performance score (20%)
     * - Availability duration score (10%)
     */
    protected function selectPartnerByScore($partners, ?float $lat, ?float $lng): ?DeliveryPartner
    {
        // MVP: Simply return first available partner
        // TODO: Implement proper scoring

        $scoredPartners = $partners->map(function ($partner) use ($lat, $lng) {
            return [
                'partner' => $partner,
                'score' => $this->calculatePartnerScore($partner, $lat, $lng),
            ];
        });

        $best = $scoredPartners->sortByDesc('score')->first();

        return $best ? $best['partner'] : null;
    }

    /**
     * Calculate partner score for assignment
     * Placeholder - returns 100 for all partners
     */
    protected function calculatePartnerScore(DeliveryPartner $partner, ?float $lat, ?float $lng): float
    {
        $score = 100.0;

        // Distance score (placeholder)
        $distanceScore = $this->calculateDistanceScore($partner, $lat, $lng);

        // Load balance score (placeholder)
        $loadScore = $this->calculateLoadBalanceScore($partner);

        // Performance score (placeholder)
        $performanceScore = $this->calculatePerformanceScore($partner);

        // Weighted average (disabled for MVP)
        // $score = ($distanceScore * 0.4) + ($loadScore * 0.3) + ($performanceScore * 0.3);

        return $score;
    }

    /**
     * Calculate distance-based score
     * Placeholder - to be implemented with proper geolocation
     */
    protected function calculateDistanceScore(DeliveryPartner $partner, ?float $lat, ?float $lng): float
    {
        if (!$lat || !$lng || !$partner->location_lat || !$partner->location_lng) {
            return 50.0; // Default score when location unavailable
        }

        // TODO: Calculate actual distance using Haversine formula
        // $distance = $this->calculateHaversineDistance(
        //     $partner->location_lat, $partner->location_lng,
        //     $lat, $lng
        // );
        // return max(0, 100 - ($distance * 10)); // Score decreases with distance

        return 50.0; // Placeholder
    }

    /**
     * Calculate load balance score based on daily assignments
     * Placeholder - to be implemented with order history
     */
    protected function calculateLoadBalanceScore(DeliveryPartner $partner): float
    {
        // TODO: Query today's completed orders for this partner
        // $todayOrders = $partner->orders()
        //     ->whereDate('created_at', today())
        //     ->count();
        // return max(0, 100 - ($todayOrders * 5)); // Lower score for more orders

        return 50.0; // Placeholder
    }

    /**
     * Calculate performance score based on delivery metrics
     * Placeholder - to be implemented with ratings/metrics
     */
    protected function calculatePerformanceScore(DeliveryPartner $partner): float
    {
        // TODO: Calculate based on:
        // - Average delivery time
        // - Customer ratings
        // - Cancellation rate
        // - On-time delivery rate

        return 50.0; // Placeholder
    }

    /**
     * Calculate distance between two points using Haversine formula
     * Returns distance in kilometers
     */
    protected function calculateHaversineDistance(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $earthRadius = 6371; // km

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Enable auto-assignment mode
     */
    public function enableAutoAssignment(): void
    {
        $this->autoAssignmentEnabled = true;
    }

    /**
     * Disable auto-assignment mode
     */
    public function disableAutoAssignment(): void
    {
        $this->autoAssignmentEnabled = false;
    }

    /**
     * Check if auto-assignment is enabled
     */
    public function isAutoAssignmentEnabled(): bool
    {
        return $this->autoAssignmentEnabled;
    }
}
