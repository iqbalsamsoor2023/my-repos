<?php

namespace App\Events;

use App\Models\SupportTicket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportTicketCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The supportTicket instance.
     *
     * @var SupportTicket
     */
    public $supportTicket;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(SupportTicket $supportTicket)
    {
        $this->supportTicket = $supportTicket;
    }
}
