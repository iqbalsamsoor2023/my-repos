<?php

namespace App\Services;

use App\Models\PrivateClaimItem;
use App\Models\Residence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Exceptions\GeneralException;

class PrivateClaimItemService
{
    public function index(Request $request)
    {
        $residenceId = $request->residence_id;

        $privateClaimItem = PrivateClaimItem::with([
            'privateClaimItemSetting' => function ($q) use ($residenceId) {
                $q->select(
                    'id',
                    'private_claim_item_id',
                    'residence_id',
                    'warranty_period'
                )->where('residence_id', $residenceId);
            }
        ])
            ->where('private_claim_category_id', $request->private_claim_category_id)
            ->whereHas('visibilitySettings', function ($q) use ($residenceId) {
                $q->where('residence_id', $residenceId)
                  ->where('is_enabled', 1);
            })
            ->orderBy('id')
            ->paginate(25);

        $residence = Residence::with('warrantySetting')
            ->find($residenceId);

        if (! $residence) {
            throw new GeneralException(
                JsonResponse::HTTP_BAD_REQUEST,
                'Residence not found.'
            );
        }

        // Only add "Others" if warranty setting exists and has_other_option is enabled
        if (
            $residence->warrantySetting?->has_other_option &&
            $privateClaimItem->currentPage() == $privateClaimItem->lastPage()
        ) {
            $privateClaimItem->getCollection()->push([
                'id' => null,
                'name' => 'Others',
                'name_th' => 'Others',
            ]);
        }

        return $privateClaimItem;
    }
}