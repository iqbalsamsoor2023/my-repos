<?php

namespace App\Actions\Facility;

use App\Enums\FacilityAndAmenity\FacilityAmenityTypeEnum;
use App\Models\ResidenceAmenity;

class GetFacilityAction
{
    public function execute($request)
    {
        $residenceAmenities = ResidenceAmenity::with([
            'facilityAndAmenity',
            'amenityRate',
            'residenceAmenityOptions' => function ($query) {
                $query->where('is_active', true);
            },
            'amenityTimeslots' => function ($query) {
                $query->where('is_active', true);
            },
        ])
            ->where('is_active', true)
            ->where('is_bookable', true)
            ->whereHas('facilityAndAmenity', function ($query) {
                $query->where('type', FacilityAmenityTypeEnum::AMENITY->value);
            });

        if (isset($request->residence_id)) {
            $residenceAmenities = $residenceAmenities->where('residence_id', $request->residence_id);
        }

        return $residenceAmenities->orderBy('id', 'DESC')->paginate(10);
    }
}
