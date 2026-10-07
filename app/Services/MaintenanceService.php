<?php

namespace App\Services;

use App\Actions\Maintenance\CheckMaintenanceWarrantyAction;
use App\Actions\Maintenance\CreateMaintenanceAction;
use App\Actions\Maintenance\CreateMaintenanceProgressAction;
use App\Actions\Maintenance\GetMaintenanceAction;
use App\Actions\Maintenance\GetOneMaintenanceAction;
use App\Actions\Maintenance\SendMaintenanceUpdateNotification;
use App\Actions\Maintenance\StoreCommentAction;
use App\Actions\Maintenance\UpdateMaintenanceAction;
use App\Http\Requests\Maintenance\StoreCommentRequest;
use App\Http\Requests\Maintenance\StoreMaintenanceRequest;
use App\Http\Requests\Maintenance\UpdateMaintenanceRequest;
use App\Models\Amenity;
use App\Models\ClaimableTitleFacilityAndAmenity;
use App\Models\Maintenance;
use App\Models\PrivateClaimItem;
use App\Models\PrivateClaimItemSetting;
use App\Models\PrivateClaimItemTitle;
use App\Models\ResidenceAmenity;
use App\Models\ResidenceAmenityOption;
use App\Models\Unit;
use Illuminate\Http\Request;

class MaintenanceService
{
    public function index(Request $request)
    {
        $getMaintenanceAction = new GetMaintenanceAction;
        $maintenance = $getMaintenanceAction->execute($request);

        return $maintenance;
    }

    public function create(StoreMaintenanceRequest $request)
    {
        if ($request->maintainable_type == 'App\Models\Unit') {
            $unit = Unit::whereId($request->maintainable_id)->firstOrFail();

            if ($request->warranty_checker == 1) {
                $is_in_warranty = new CheckMaintenanceWarrantyAction;
                $is_in_warranty->execute($request, $unit);
            }

            if (empty($request->private_claim_item_id) == false && $request->private_claim_item_id == 'Others') {
                $request->merge([
                    'other_private_claim_item' => $request->other_private_claim_item,
                ]);
            } elseif (empty($request->private_claim_item_id) == false && $request->private_claim_item_id != 'Others') {
                $model = $unit;

                $item  = PrivateClaimItem::find($request->private_claim_item_id);
                $title = PrivateClaimItemTitle::find($request->private_claim_item_title_id);

                $privateClaimSnapshot = [
                    'item' => $item ? [
                        'id' => $item->id,
                        'name' => $item->name,
                        'name_th' => $item->name_th,
                    ] : null,

                    'title' => $title ? [
                        'id' => $title->id,
                        'name' => $title->option_name,
                        'name_th' => $title->option_name_th,
                    ] : null,
                ];

                $request->merge(['private_claim_snapshot' => $privateClaimSnapshot]);
            } else {
                $model = $unit;
            }
        } else {
            if ($request->maintainable_type == 'App\Models\ResidenceAmenity' || $request->maintainable_type == 'App\Models\ClaimableItem') {
                $request->merge(['maintainable_type' => ResidenceAmenity::class]);

                $model = ResidenceAmenity::whereId($request->maintainable_id)
                    ->where('is_active', true)
                    ->where('is_claimable', true)
                    ->firstOrFail();

                if ($model && isset($request->claimable_title_id)) {
                    ClaimableTitleFacilityAndAmenity::where('claimable_title_id', $request->claimable_title_id)
                        ->where('facility_and_amenity_id', $model->facility_and_amenity_id)
                        ->firstOrFail();
                }
            } elseif ($request->maintainable_type == 'App\Models\ResidenceAmenityOption') {
                $model = ResidenceAmenityOption::whereId($request->maintainable_id)
                    ->where('is_active', true)
                    ->whereHas('residenceAmenity', function ($query) {
                        return $query->where('is_active', true)->where('is_claimable', true);
                    })
                    ->firstOrFail();

                if ($model && isset($request->claimable_title_id)) {
                    ClaimableTitleFacilityAndAmenity::where('claimable_title_id', $request->claimable_title_id)
                        ->where('facility_and_amenity_id', $model?->residenceAmenity->facility_and_amenity_id)
                        ->firstOrFail();
                }
            }

            $request->merge([
                'maintainable_id' => $model->id,
            ]);
        }

        $maintenance = new CreateMaintenanceAction;
        $maintenance = $maintenance->execute($request, $model);

        return $maintenance;
    }

    public function show(int $id)
    {
        $getOneMaintenanceAction = new GetOneMaintenanceAction;
        $maintenance = $getOneMaintenanceAction->execute($id);

        return $maintenance;
    }

    public function update(UpdateMaintenanceRequest $request, int $id)
    {
        $maintenance = Maintenance::findOrFail($id);

        if ($request->has('progress_description') && (is_null($request->progress_description) == false)) {
            $maintenanceProgressAction = new CreateMaintenanceProgressAction;
            $maintenanceProgressAction->execute($request, $maintenance);
        }

        $maintenanceAction = new UpdateMaintenanceAction;
        $maintenance = $maintenanceAction->execute($request, $maintenance);

        $send_notification = new SendMaintenanceUpdateNotification;
        $send_notification->execute($maintenance);

        return $maintenance;
    }

    public function comment(StoreCommentRequest $request, int $id)
    {
        $maintenance = Maintenance::findOrFail($id);
        $commentAction = new StoreCommentAction;
        $comment = $commentAction->execute($request, $maintenance);

        return $comment;
    }
}
