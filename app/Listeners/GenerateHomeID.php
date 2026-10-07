<?php

namespace App\Listeners;

use App\Events\UnitCreated;
use App\Helpers\HomeIdGenerator;

class GenerateHomeID
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
        $unit->home_id = HomeIdGenerator::generate($unit);
        $unit->save();
    }
}
