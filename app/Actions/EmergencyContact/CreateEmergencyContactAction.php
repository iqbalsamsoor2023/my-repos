<?php

namespace App\Actions\EmergencyContact;

use App\Exceptions\GeneralException;
use App\Http\Requests\EmergencyContact\StoreEmergencyContactRequest;
use App\Models\EmergencyContact;
use Illuminate\Http\JsonResponse;

class CreateEmergencyContactAction
{
    public function execute(StoreEmergencyContactRequest $request)
    {
        $emergency_contact = EmergencyContact::create([
            'department_type' => $request->department_type,
            'name' => $request->name,
            'contact_no' => $request->contact_no,
            'coverage_mode' => $request->coverage_mode,
            'is_active' => $request->is_active,
        ]);

        if (! $emergency_contact) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed creating emergency contact');
        }

        return $emergency_contact;
    }
}
