<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The order whose status was updated
     */
    public Order $order;

    /**
     * The previous status
     */
    public string $previousStatus;

    /**
     * The new status
     */
    public string $newStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, string $previousStatus, string $newStatus)
    {
        $this->order = $order;
        $this->previousStatus = $previousStatus;
        $this->newStatus = $newStatus;
    }
}
