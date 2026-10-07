<?php

namespace App\Events;

use App\Models\Residence;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ResidenceCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The residence instance.
     *
     * @var Residence
     */
    public $residence;

    /**
     * Create a new event instance.
     *
     * @param Residence $residence
     * @return void
     */
    public function __construct(Residence $residence)
    {
        $this->residence = $residence;
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
