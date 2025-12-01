<?php

namespace App\Events;

use App\Models\Order;
use App\Models\DeliveryPartner;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The order that was assigned
     */
    public Order $order;

    /**
     * The delivery partner assigned to the order
     */
    public DeliveryPartner $partner;

    /**
     * The assignment method (manual or auto)
     */
    public string $assignmentMethod;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, DeliveryPartner $partner, string $assignmentMethod = 'manual')
    {
        $this->order = $order;
        $this->partner = $partner;
        $this->assignmentMethod = $assignmentMethod;
    }
}
