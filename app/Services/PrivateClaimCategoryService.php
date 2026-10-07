<?php

namespace App\Services;

use App\Enums\Residence\MoobanType;
use App\Models\PrivateClaimCategory;
use App\Models\Residence;
use Illuminate\Http\Request;

class PrivateClaimCategoryService
{
    public function index(Request $request)
    {
        $residenceId = $request->residence_id;

        $privateclaimcategory = PrivateClaimCategory::whereHas('privateClaimItems.visibilitySettings', function ($q) use ($residenceId) {
            $q->where('residence_id', $residenceId)
                ->where('is_enabled', true);
        })
            ->orderBy('id')
            ->get();

        $residence = Residence::with('warrantySetting')->where('id', $residenceId)->first();

        if (
            $residence?->mooban_type != MoobanType::PUBLIC->value &&
            $residence?->warrantySetting?->has_other_option == true
        ) {
            $privateclaimcategory->push([
                'id' => null,
                'name' => 'Others',
                'name_th' => 'Others',
            ]);
        }
        
        return $privateclaimcategory;
    }
}