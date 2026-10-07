<?php

namespace App\Listeners;

use App\Events\ResidenceCreated;
use App\Models\Amenity;

class GenerateOtherOption
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
     * @param ResidenceCreated $event
     * @return void
     */
    public function handle(ResidenceCreated $event)
    {
        $residence = $event->residence;

        $this->createAmenity($residence);
    }

    private function createAmenity($residence): void
    {
        Amenity::create([
            'residence_id' => $residence->id,
            'amenity_name' => 'Others',
        ]);
    }
}
