<?php

namespace App\Listeners;

use App\Events\ParcelCreated;
use App\Helpers\ParcelIdGenerator;

class GenerateParcelID
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
     * @param ParcelCreated $event
     * @return void
     */
    public function handle(ParcelCreated $event)
    {
        $parcel = $event->parcel;
        $parcel->parcel_generated_no = ParcelIdGenerator::generate($parcel);
        $parcel->save();
    }
}
