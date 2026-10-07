<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use App\Models\Pet;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PetCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The pet instance.
     *
     * @var Pet
     */
    public $pet;

    /**
     * Create a new event instance.
     *
     * @param Pet $pet
     * @return void
     */
    public function __construct(Pet $pet)
    {
        $this->pet = $pet;
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
