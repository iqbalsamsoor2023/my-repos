<?php

namespace App\Services;

use App\Actions\EmergencyContact\CreateEmergencyContactAction;
use App\Actions\EmergencyContact\GetEmergencyContactAction;
use App\Actions\EmergencyContact\GetOneEmergencyContactAction;
use App\Http\Requests\EmergencyContact\StoreEmergencyContactRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class EmergencyContactService
{
    public function index(Request $request)
    {
        $getEmergencyContactAction = new GetEmergencyContactAction;
        $emergencyContacts = $getEmergencyContactAction->execute($request);

        return $emergencyContacts;
    }

    public function create(StoreEmergencyContactRequest $request)
    {
        $emergencyContact = new CreateEmergencyContactAction;

        return $emergencyContact->execute($request);
    }

    public function show(int $id): Model
    {
        $getOneEmergencyContactAction = new GetOneEmergencyContactAction;
        $emergencyContact = $getOneEmergencyContactAction->execute($id);

        return $emergencyContact;
    }
}
