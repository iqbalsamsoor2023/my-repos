<?php

namespace App\Actions\Maintenance;

use App\Exceptions\GeneralException;
use App\Http\Requests\Maintenance\UpdateMaintenanceRequest;
use App\Models\Maintenance;
use App\Models\MaintenanceProgression;
use Illuminate\Http\JsonResponse;

class CreateMaintenanceProgressAction
{
    public function execute(UpdateMaintenanceRequest $request, Maintenance $maintenance)
    {
        $request->merge([
            'maintenance_id' => $maintenance->id,
        ]);

        $maintenance_progression = MaintenanceProgression::create($request->only([
            'maintenance_id',
            'progress_description',
        ]));

        if (! $maintenance_progression) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed updating maintenance progress');
        }

        $this->uploadMaintenanceProgressImage($maintenance_progression, $request);

        return $maintenance_progression;
    }

    private function uploadMaintenanceProgressImage(MaintenanceProgression $maintenance_progression, $request)
    {
        if ($request->hasFile('progress_image')) {
            $maintenance_progression->addMediaFromRequest('progress_image')->withCustomProperties(['type' => 'maintenance_progress'])->toMediaCollection('maintenance_progression_image');
        }
    }
}
