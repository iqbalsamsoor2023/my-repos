<?php

namespace App\Services;

use App\Actions\Pet\CreatePetAction;
use App\Actions\Pet\DeletePetAction;
use App\Actions\Pet\GetPetAction;
use App\Actions\Pet\UpdatePetAction;
use App\Http\Requests\Pet\StorePetRequest;
use App\Http\Requests\Pet\UpdatePetRequest;
use App\Models\Pet;
use Illuminate\Http\Request;

class PetService
{
    public function index(Request $request)
    {
        $getPetAction = new GetPetAction;
        $pets = $getPetAction->execute($request);

        return $pets;
    }

    public function create(StorePetRequest $request)
    {
        $petAction = new CreatePetAction;
        $pet = $petAction->execute($request);

        return $pet;
    }

    public function update(UpdatePetRequest $request, int $id)
    {
        $pet = Pet::findOrFail($id);
        $petAction = new UpdatePetAction;
        $pet = $petAction->execute($request, $pet);

        return $pet;
    }

    public function delete(int $id)
    {
        $pet = Pet::findOrFail($id);
        $petAction = new DeletePetAction;
        $pet = $petAction->execute($pet);

        return $pet;
    }
}
