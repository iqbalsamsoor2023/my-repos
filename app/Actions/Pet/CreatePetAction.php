<?php

namespace App\Actions\Pet;

use App\Exceptions\GeneralException;
use App\Http\Requests\Pet\StorePetRequest;
use App\Models\Pet;
use Illuminate\Http\JsonResponse;

class CreatePetAction
{
    public function execute(StorePetRequest $request)
    {
        $pet = Pet::create($request->only([
            'unit_id',
            'user_id',
            'breed',
            'type',
            'year',
            'generated_pet_no',
            'created_by',
        ]));

        if (! $pet) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed creating pet');
        }

        $this->uploadAllSidesOfPetImages($pet, $request);

        return $pet;
    }

    private function uploadAllSidesOfPetImages(Pet $pet, $request)
    {
        $pet->addMedia($request->file('image_front'))->withCustomProperties(['side' => 'front'])->toMediaCollection('front');
        $pet->addMedia($request->file('image_right'))->withCustomProperties(['side' => 'right'])->toMediaCollection('right');
        $pet->addMedia($request->file('image_back'))->withCustomProperties(['side' => 'back'])->toMediaCollection('back');
        $pet->addMedia($request->file('image_left'))->withCustomProperties(['side' => 'left'])->toMediaCollection('left');
        $pet->addMedia($request->file('image_top'))->withCustomProperties(['side' => 'top'])->toMediaCollection('top');
        $pet->addMedia($request->file('image_bottom'))->withCustomProperties(['side' => 'bottom'])->toMediaCollection('bottom');
    }
}
