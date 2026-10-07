<?php

namespace App\Events;

use App\Models\Unit;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UnitCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The unit instance.
     *
     * @var Unit
     */
    public $unit;

    /**
     * Create a new event instance.
     *
     * @param Unit $unit
     * @return void
     */
    public function __construct(Unit $unit)
    {
        $this->unit = $unit;
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
