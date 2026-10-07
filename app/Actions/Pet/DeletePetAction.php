<?php

namespace App\Actions\Pet;

use App\Exceptions\GeneralException;
use App\Models\Pet;
use Illuminate\Http\JsonResponse;

class DeletePetAction
{
    public function execute(Pet $pet)
    {
        if ($pet->delete()) {
            $pet->clearMediaCollection();

            return;
        }

        throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed deleting pet');
    }
}
