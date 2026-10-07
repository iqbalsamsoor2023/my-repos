<?php

namespace App\Events;

use App\Models\UnitUser;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UnitUserCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The unitUser instance.
     *
     * @var UnitUser
     */
    public $unitUser;

    /**
     * Create a new event instance.
     *
     * @param UnitUser $unitUser
     * @return void
     */
    public function __construct(UnitUser $unitUser)
    {
        $this->unitUser = $unitUser;
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
