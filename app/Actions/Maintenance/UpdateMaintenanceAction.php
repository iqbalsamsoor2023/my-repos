<?php

namespace App\Actions\Maintenance;

use App\Enums\Maintenance\MaintenanceStatus;
use App\Exceptions\GeneralException;
use App\Http\Requests\Maintenance\UpdateMaintenanceRequest;
use App\Models\Maintenance;
use Illuminate\Http\JsonResponse;

class UpdateMaintenanceAction
{
    public function execute(UpdateMaintenanceRequest $request, Maintenance $maintenance)
    {
        $maintenance = $this->updateMaintenanceStatus($maintenance, $request);

        $this->uploadMaintenanceImage($maintenance, $request);

        return $maintenance;
    }

    private function updateMaintenanceStatus(Maintenance $maintenance, $request)
    {
        if ($request->has('is_verified') && $request->is_verified == false) {
            $request['status'] = MaintenanceStatus::IN_PROGRESS->value;
        } elseif ($request->has('is_verified') && $request->is_verified == true) {
            $request['status'] = MaintenanceStatus::COMPLETE->value;
        }

        $maintenance->update($request->only([
            'status',
            'completion_datetime',
            'completed_remark',
            'is_verified',
            'verification_description',
            'rating',
        ]));

        if (! $maintenance) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed updating maintenance');
        }

        return $maintenance;
    }

    private function uploadMaintenanceImage(Maintenance $maintenance, $request)
    {
        if ($request->hasFile('completed_image')) {
            $maintenance->addMediaFromRequest('completed_image')->withCustomProperties(['type' => 'maintenance_completed'])->toMediaCollection('maintenance_completed_images');
        }

        if ($request->hasFile('verification_image')) {
            $maintenance->addMediaFromRequest('verification_image')->withCustomProperties(['type' => 'maintenance_verification'])->toMediaCollection('maintenance_verification_image');
        }
    }
}
