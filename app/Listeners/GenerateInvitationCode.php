<?php

namespace App\Listeners;

use App\Events\UnitCreated;
use App\Helpers\InvitationCodeGenerator;

class GenerateInvitationCode
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
     * @param UnitCreated $event
     * @return void
     */
    public function handle(UnitCreated $event)
    {
        $unit = $event->unit;
        $unit->invitation_code_owner = InvitationCodeGenerator::generate($unit, 'owner');
        $unit->invitation_code_tenant = InvitationCodeGenerator::generate($unit, 'tenant');
        $unit->save();
    }
}
