<?php

namespace App\Listeners;

use App\Events\SupportTicketCreated;
use App\Helpers\CaseIdGenerator;

class GenerateCaseID
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(SupportTicketCreated $event)
    {
        $supportTicket = $event->supportTicket;
        $supportTicket->case_generated_no = CaseIdGenerator::generate($supportTicket);
        $supportTicket->save();
    }
}
