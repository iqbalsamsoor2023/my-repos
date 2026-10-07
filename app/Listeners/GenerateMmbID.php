<?php

namespace App\Listeners;

use App\Events\UnitUserCreated;
use App\Helpers\MmbIdGenerator;
use App\Models\UnitUser;

class GenerateMmbID
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
     * @param UnitUserCreated $event
     * @return void
     */
    public function handle(UnitUserCreated $event)
    {
        $unitUser = $event->unitUser;
        $unitUser = UnitUser::where('unit_id', $unitUser->unit_id)->where('user_id', $unitUser->user_id)->first();
        if ($unitUser->unit) {
            $unitUser->mmb_id = MmbIdGenerator::execute($unitUser->unit);
        }
    }
}
