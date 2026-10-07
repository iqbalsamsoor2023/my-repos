<?php

namespace App\Actions\ClaimableItem;

use App\Models\ResidenceAmenity;
use Illuminate\Database\Eloquent\Model;

class GetOneClaimableItemAction
{
    public function execute(int $id): ?Model
    {
        return ResidenceAmenity::with([
            'facilityAndAmenity',
            'residenceAmenityOptions' => function ($query) {
                $query->where('is_active', true);
            },
        ])
            ->where('is_active', true)
            ->where('is_claimable', true)
            ->findOrFail($id);
    }
}
