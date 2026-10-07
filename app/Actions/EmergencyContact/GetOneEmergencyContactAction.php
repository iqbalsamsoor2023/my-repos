<?php

namespace App\Actions\EmergencyContact;

use App\Models\EmergencyContact;
use Illuminate\Database\Eloquent\Model;

class GetOneEmergencyContactAction
{
    public function execute(int $id): Model
    {
        return EmergencyContact::with('districtEmergencyContacts')->findOrFail($id);
    }
}
