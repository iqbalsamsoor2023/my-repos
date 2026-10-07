<?php

namespace App\Actions\Residence;

use App\Models\Residence;

class GetResidenceAction
{
    public function execute($request)
    {
        $residences = Residence::with(
            'receptionist',
            'subdistrict',
            'subdistrict.district',
            'subdistrict.district.province',
            'developer',
            'propertyManagementUser',
        );

        if (isset($request->subdistrict_id)) {
            $residences = $residences->where('subdistrict_id', $request->subdistrict_id);
        }

        if (isset($request->sgoc_residence_guard_user_id)) {
            $residences = $residences->where('sgoc_residence_guard_user_id', $request->sgoc_residence_guard_user_id);
        }

        if (isset($request->has_pagination) && ($request->has_pagination == false)) {
            return $residences->get();
        }

        return $residences->paginate(25);
    }
}
