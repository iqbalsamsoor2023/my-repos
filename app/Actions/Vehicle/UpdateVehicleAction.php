<?php

namespace App\Actions\Vehicle;

use App\Exceptions\GeneralException;
use App\Http\Requests\Vehicle\UpdateVehicleRequest;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;

class UpdateVehicleAction
{
    public function execute(UpdateVehicleRequest $request, Vehicle $vehicle)
    {
        // Prepare data with backward compatibility for purchase_year/model_year
        $data = $request->only([
            'province_id',
            'vehicle_model_id',
            'insurance_company_id',
            'plate_number',
            'generated_vehicle_no',
            'roadtax_expiry_date',
            'insurance_expiry_date',
            'policy_no',
            'is_access_card',
            'is_car_sticker',
            'fuel_type',
        ]);

        // Handle backward compatibility: old apps send 'purchase_year', new apps send 'model_year'
        // Database only has 'model_year' column
        if ($request->has('purchase_year')) {
            $data['model_year'] = $request->purchase_year;
        } elseif ($request->has('model_year')) {
            $data['model_year'] = $request->model_year;
        }

        $vehicle->update($data);

        if (! $vehicle) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed updating vehicle');
        }

        $this->uploadVehicleImage($vehicle, $request);
        $this->uploadRoadTaxImage($vehicle, $request);

        return $vehicle;
    }

    private function uploadVehicleImage(Vehicle $vehicle, $request)
    {
        if ($request->hasFile('image')) {
            $vehicle->clearMediaCollection('vehicle_image');
            $vehicle->addMediaFromRequest('image')->withCustomProperties(['type' => 'vehicle'])->toMediaCollection('vehicle_image');
        }

        if ($request->hasFile('lpr_image')) {
            $vehicle->clearMediaCollection('vehicle_lpr_image');
            $vehicle->addMediaFromRequest('lpr_image')->withCustomProperties(['type' => 'vehicle'])->toMediaCollection('vehicle_lpr_image');
        }

        if ($request->hasFile('front_image')) {
            $vehicle->clearMediaCollection('vehicle_front_image');
            $vehicle->addMediaFromRequest('front_image')->withCustomProperties(['type' => 'vehicle'])->toMediaCollection('vehicle_front_image');
        }

        if ($request->hasFile('back_image')) {
            $vehicle->clearMediaCollection('vehicle_back_image');
            $vehicle->addMediaFromRequest('back_image')->withCustomProperties(['type' => 'vehicle'])->toMediaCollection('vehicle_back_image');
        }

        if ($request->hasFile('right_side_image')) {
            $vehicle->clearMediaCollection('vehicle_right_image');
            $vehicle->addMediaFromRequest('right_side_image')->withCustomProperties(['type' => 'vehicle'])->toMediaCollection('vehicle_right_image');
        }

        if ($request->hasFile('left_side_image')) {
            $vehicle->clearMediaCollection('vehicle_left_image');
            $vehicle->addMediaFromRequest('left_side_image')->withCustomProperties(['type' => 'vehicle'])->toMediaCollection('vehicle_left_image');
        }
    }

    private function uploadRoadTaxImage(Vehicle $vehicle, $request)
    {
        if ($request->hasFile('roadtax_image')) {
            $vehicle->clearMediaCollection('roadtax_image');
            $vehicle->addMediaFromRequest('roadtax_image')->withCustomProperties(['type' => 'roadtax'])->toMediaCollection('roadtax_image');
        }
    }
}
