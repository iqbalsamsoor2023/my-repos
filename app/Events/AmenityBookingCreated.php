<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use App\Models\AmenityBooking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AmenityBookingCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The amenityBooking instance.
     *
     * @var AmenityBooking
     */
    public $amenityBooking;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(AmenityBooking $amenityBooking)
    {
        $this->amenityBooking = $amenityBooking;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    // public function broadcastOn()
    // {
    //     return new PrivateChannel('channel-name');
    // }

}
