<?php

namespace App\Actions\PublicClaimItem;

use App\Models\FacilityAndAmenity;
use App\Models\ResidenceAmenity;
use Illuminate\Http\Request;

class GetPublicClaimItemAction
{
    public function execute(Request $request)
    {
        $otherFacilityAndAmenityId = FacilityAndAmenity::where('name', 'Others')->value('id');

        $amenities = ResidenceAmenity::with(['facilityAndAmenity', 'residenceAmenityOptions'])
            ->where('is_active', true)
            ->where('is_claimable', true)
            ->where('facility_and_amenity_id', '!=', $otherFacilityAndAmenityId); // Exclude 'Others' facility according to CS request;

        // to be removed after discontinuing v2 app support
        if (! $request->header('X-App-Version')) {
            $amenities = ResidenceAmenity::with(['facilityAndAmenity', 'residenceAmenityOptions'])
                ->where('is_active', true)
                ->where('is_claimable', true);
        }

        if (isset($request->residence_id)) {
            $amenities = $amenities->where('residence_id', $request->residence_id);
        }

        if (isset($request->name)) {
            $amenities = $amenities->whereHas('facilityAndAmenity', function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->name.'%')
                    ->orWhere('name_in_thai', 'like', '%'.$request->name.'%');
            });
        }

        return $amenities->orderBy('id', 'DESC')->paginate(10);
    }
}
