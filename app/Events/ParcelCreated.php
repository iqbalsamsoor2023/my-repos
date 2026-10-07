<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use App\Models\Parcel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ParcelCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The parcel instance.
     *
     * @var Parcel
     */
    public $parcel;

    /**
     * Create a new event instance.
     *
     * @param Parcel $parcel
     * @return void
     */
    public function __construct(Parcel $parcel)
    {
        $this->parcel = $parcel;
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
