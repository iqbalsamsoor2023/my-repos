<?php

namespace App\Listeners;

use App\Events\VehicleCreated;
use App\Helpers\VehicleNumberGenerator;

class GenerateVehicleNumber
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
     * @param VehicleCreated $event
     * @return void
     */
    public function handle(VehicleCreated $event)
    {
        $vehicle = $event->vehicle;
        $vehicle->generated_vehicle_no = VehicleNumberGenerator::generate($vehicle);
        $vehicle->save();
    }
}
