<?php

namespace App\Actions\ClaimableItem;

use App\Models\ResidenceAmenity;
use Illuminate\Http\Request;

class GetClaimableItemAction
{
    public function execute(Request $request)
    {
        $claimableItems = ResidenceAmenity::with([
            'facilityAndAmenity',
            'residenceAmenityOptions' => function ($query) {
                $query->where('is_active', true);
            },
        ])
            ->where('is_active', true)
            ->where('is_claimable', true);

        if (isset($request->residence_id)) {
            $claimableItems = $claimableItems->where('residence_id', $request->residence_id);
        }

        if (isset($request->item_name)) {
            $claimableItems = $claimableItems->whereHas('facilityAndAmenity', function ($query) use ($request) {
                $query->where('name', '!=', $request->item_name)
                    ->orWhere('name_in_thai', '!=', $request->item_name);
            });
        }

        return $claimableItems->get();
    }
}
