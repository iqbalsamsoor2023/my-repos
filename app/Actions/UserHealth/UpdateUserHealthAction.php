<?php

namespace App\Actions\UserHealth;

use App\Exceptions\GeneralException;
use App\Http\Requests\UserHealth\UpdateUserHealthRequest;
use App\Models\UserHealth;
use Illuminate\Http\JsonResponse;

class UpdateUserHealthAction
{
    public function execute(UpdateUserHealthRequest $request, UserHealth $userHealth)
    {
        $success = $userHealth->update($request->only([
            'blood_type',
            'height',
            'weight',
            'health_questionnaire_answers',
        ]));

        if (! $success) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed updating user health.');
        }

        $user = $userHealth->user;

        if (! $user) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'User not found for insurance update.');
        }

        $user->fill($request->only([
            'insurance_company_id',
            'insurance_policy_no',
            'insurance_expiry_date',
        ]));

        if (! $user->save()) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed to update user insurance details.');
        }

        return $userHealth;
    }
}
