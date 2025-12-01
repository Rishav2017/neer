<?php

namespace App\Events;

use App\Models\Order;
use App\Models\DeliveryPartner;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderUnassigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The order that was unassigned
     */
    public Order $order;

    /**
     * The delivery partner that was unassigned (may be null if already deleted)
     */
    public ?DeliveryPartner $previousPartner;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, ?DeliveryPartner $previousPartner = null)
    {
        $this->order = $order;
        $this->previousPartner = $previousPartner;
    }
}
