<?php

namespace App\Actions\Pet;

use App\Exceptions\GeneralException;
use App\Http\Requests\Pet\UpdatePetRequest;
use App\Models\Pet;
use Illuminate\Http\JsonResponse;

class UpdatePetAction
{
    public function execute(UpdatePetRequest $request, Pet $pet)
    {
        $pet->update($request->only([
            'unit_id',
            'user_id',
            'breed',
            'type',
            'year',
            'generated_pet_no',
            'created_by',
        ]));

        if (! $pet) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed updating pet');
        }

        $this->uploadAllSidesOfPetImages($pet, $request);

        return $pet;
    }

    private function uploadAllSidesOfPetImages(Pet $pet, $request)
    {
        if ($request->hasFile('image_front')) {
            $mediaItems = $pet->getMedia('front');
            $mediaItems[0]->delete();
            $pet->addMedia($request->file('image_front'))->withCustomProperties(['side' => 'front'])->toMediaCollection('front');
        }

        if ($request->hasFile('image_right')) {
            $mediaItems = $pet->getMedia('right');
            $mediaItems[0]->delete();
            $pet->addMedia($request->file('image_right'))->withCustomProperties(['side' => 'right'])->toMediaCollection('right');
        }

        if ($request->hasFile('image_back')) {
            $mediaItems = $pet->getMedia('back');
            $mediaItems[0]->delete();
            $pet->addMedia($request->file('image_back'))->withCustomProperties(['side' => 'back'])->toMediaCollection('back');
        }

        if ($request->hasFile('image_left')) {
            $mediaItems = $pet->getMedia('left');
            $mediaItems[0]->delete();
            $pet->addMedia($request->file('image_left'))->withCustomProperties(['side' => 'left'])->toMediaCollection('left');
        }

        if ($request->hasFile('image_top')) {
            $mediaItems = $pet->getMedia('top');
            $mediaItems[0]->delete();
            $pet->addMedia($request->file('image_top'))->withCustomProperties(['side' => 'top'])->toMediaCollection('top');
        }

        if ($request->hasFile('image_bottom')) {
            $mediaItems = $pet->getMedia('bottom');
            $mediaItems[0]->delete();
            $pet->addMedia($request->file('image_bottom'))->withCustomProperties(['side' => 'bottom'])->toMediaCollection('bottom');
        }
    }
}
