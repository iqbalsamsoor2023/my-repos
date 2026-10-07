<?php

namespace App\Actions\EmergencyContact;

use App\Enums\EmergencyContact\DepartmentType;
use App\Models\EmergencyContact;

class GetEmergencyContactAction
{
    public function execute($request)
    {
        $emergencyContacts = EmergencyContact::with('districtEmergencyContacts');

        if (isset($request->district_id)) {
            $emergencyContacts = $emergencyContacts->whereHas('districtEmergencyContacts', function ($query) use ($request) {
                $query->where('thailand_district_id', $request->district_id);
            });
        }

        if (isset($request->type_id)) {
            $emergencyContacts = $emergencyContacts->where('department_type', DepartmentType::fromTypeId($request->type_id));
        }

        return $emergencyContacts->paginate(25);
    }
}
