<?php

namespace App\Events;

use App\Models\Maintenance;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MaintenanceCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The maintenance instance.
     *
     * @var maintenance
     */
    public $maintenance;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Maintenance $maintenance)
    {
        $this->maintenance = $maintenance;
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
