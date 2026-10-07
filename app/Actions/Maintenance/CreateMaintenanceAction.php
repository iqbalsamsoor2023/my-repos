<?php

namespace App\Actions\Maintenance;

use App\Enums\Maintenance\MaintenanceStatus;
use App\Exceptions\GeneralException;
use App\Http\Requests\Maintenance\StoreMaintenanceRequest;
use App\Models\ClaimableTitle;
use App\Models\Maintenance;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class CreateMaintenanceAction
{
    public function execute(StoreMaintenanceRequest $request, Model $model)
    {
        if ($request->maintainable_type == Unit::class) {
            // $request['claimable_item_details'] = [
            //     'id' => $model->id,
            //     'amenity_name' => $model->amenity_name,
            //     'warranty_period' => $model->warranty_period,
            //     'period_type' => $model->period_type,
            //     'supplier' => $model->supplier,
            //     'is_out_warranty' => $model->is_out_warranty,
            //     'remark' => $model->remark,
            // ];
        }

        if (isset($request->claimable_title_id)) {
            $claimableTitle = ClaimableTitle::find($request->claimable_title_id);

            $request['claimable_item_details'] = [
                'id' => $claimableTitle->id,
                'claimable_title_name' => $claimableTitle->name,
                'claimable_title_name_th' => $claimableTitle->name_in_thai,
            ];
        }

        $request->merge([
            'status' => MaintenanceStatus::PENDING->value,
        ]);

        $maintenance = Maintenance::create($request->only([
            'maintainable_id',
            'maintainable_type',
            'claimable_item_details',
            'miscellaneous',
            'issue_description',
            'appointment_datetime',
            'reported_by',
            'status',
            'private_claim_category_id',
            'private_claim_item_id',
            'private_claim_item_title_id',
            'private_claim_snapshot',
            'other_private_claim_item',
            // 'other_private_claim_category',
        ]));

        if (! $maintenance) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed creating maintenance');
        }

        $this->uploadMaintenanceImage($maintenance, $request);
        $sendMaintenanceCreateNotification = new SendMaintenanceCreateNotification;
        $sendMaintenanceCreateNotification->execute($maintenance);

        return $maintenance;
    }

    private function uploadMaintenanceImage(Maintenance $maintenance, $request)
    {
        if ($request->hasFile('images')) {
            foreach ($request->images as $image) {
                $maintenance->addMedia($image)->withCustomProperties(['type' => 'maintenance'])->toMediaCollection('maintenance_images');
            }
        }
    }
}
