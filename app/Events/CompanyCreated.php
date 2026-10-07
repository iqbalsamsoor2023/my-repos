<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use App\Models\Company;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompanyCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The company instance.
     *
     * @var Company
     */
    public $company;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Company $company)
    {
        $this->company = $company;
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
