<?php

namespace App\Listeners;

use App\Events\PetCreated;
use App\Helpers\PetIdGenerator;

class GeneratePetID
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
     * @param PetCreated $event
     * @return void
     */
    public function handle(PetCreated $event)
    {
        $pet = $event->pet;
        $pet->generated_pet_no = PetIdGenerator::generate($pet);
        $pet->save();
    }
}
