<?php

namespace App\Actions\UserHealth;

use App\Exceptions\GeneralException;
use App\Http\Requests\UserHealth\StoreUserHealthRequest;
use App\Models\User;
use App\Models\UserHealth;
use Illuminate\Http\JsonResponse;

class CreateUserHealthAction
{
    public function execute(StoreUserHealthRequest $request)
    {
        $userHealth = UserHealth::create($request->only([
            'user_id',
            'blood_type',
            'height',
            'weight',
            'health_questionnaire_answers',
        ]));

        if (! $userHealth) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed creating user health record.');
        }

        $user = User::find($userHealth->user_id);

        if (! $user) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'User not found for updating insurance details.');
        }

        $user->fill($request->only([
            'insurance_company_id',
            'insurance_policy_no',
            'insurance_expiry_date',
        ]));

        if (! $user) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed updating user insurance details.');
        }

        return $userHealth;
    }
}
