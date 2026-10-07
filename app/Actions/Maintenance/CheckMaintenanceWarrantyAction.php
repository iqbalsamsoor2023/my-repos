<?php

namespace App\Actions\Maintenance;

use App\Exceptions\GeneralException;
use App\Http\Requests\Maintenance\StoreMaintenanceRequest;
use App\Models\PrivateClaimItemSetting;
use App\Models\Unit;
use App\Models\WarrantySetting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class CheckMaintenanceWarrantyAction
{
    public function execute(StoreMaintenanceRequest $request, Unit $unit)
    {
        if (!empty($request->private_claim_item_id)) {

            $privateClaimItemSetting = PrivateClaimItemSetting::select('warranty_period', 'is_out_warranty', 'remark')
                ->where('private_claim_item_id', $request->private_claim_item_id)
                ->where('residence_id', $unit->residence_id)
                ->firstOrFail();

            if (!empty($privateClaimItemSetting->warranty_period)) {
                if (empty($unit->move_in_at) == true || is_null($unit->move_in_at) == true) {
                    throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Please contact PM for start the warranty');
                }

                $warrantyPeriod = Carbon::parse($unit->move_in_at)->addMonths($privateClaimItemSetting->warranty_period)->format('Y-m-d');

                $currentDate = Carbon::now()->format('Y-m-d');

                if ($privateClaimItemSetting->is_out_warranty == true) {
                    $warranty_message = isset($privateClaimItemSetting->remark) ? $privateClaimItemSetting->remark : "This claim of repair depends on the project's policy.";
                } else {
                    $warranty_message = "This claim of repair depends on the project's policy.";
                }

                if ($currentDate > $warrantyPeriod) {
                    throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, __($warranty_message));
                }
            }
        } else {
            $warranty = WarrantySetting::where('residence_id', $unit->residence_id)->firstOrFail();

            if (!empty($warranty->remark)) {
                throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, __($warranty->remark));
            }
        }
    }
}
