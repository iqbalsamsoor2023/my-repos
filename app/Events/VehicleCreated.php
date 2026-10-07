<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use App\Models\Vehicle;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VehicleCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The vehicle instance
     *
     * @var Vehicle
     */
    public $vehicle;

    /**
     * Create a new event instance.
     *
     * @param Vehicle $vehicle
     * @return void
     */
    public function __construct(Vehicle $vehicle)
    {
        $this->vehicle = $vehicle;
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
