<?php

namespace App\Actions\Vehicle;

use App\Exceptions\GeneralException;
use App\Http\Requests\Vehicle\StoreVehicleRequest;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;

class CreateVehicleAction
{
    public function execute(StoreVehicleRequest $request)
    {
        // Prepare data with backward compatibility for purchase_year/model_year
        $data = $request->only([
            'province_id',
            'unit_id',
            'user_id',
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

        $vehicle = Vehicle::create($data);

        if (! $vehicle) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed creating vehicle');
        }

        $this->uploadVehicleImage($vehicle, $request);
        $this->uploadRoadTaxImage($vehicle, $request);

        return $vehicle;
    }

    private function uploadVehicleImage(Vehicle $vehicle, $request)
    {
        if ($request->hasFile('image')) {
            $vehicle->addMediaFromRequest('image')->withCustomProperties(['type' => 'vehicle'])->toMediaCollection('vehicle_image');
        }

        if ($request->hasFile('lpr_image')) {
            $vehicle->addMediaFromRequest('lpr_image')->withCustomProperties(['type' => 'vehicle'])->toMediaCollection('vehicle_lpr_image');
        }

        if ($request->hasFile('front_image')) {
            $vehicle->addMediaFromRequest('front_image')->withCustomProperties(['type' => 'vehicle'])->toMediaCollection('vehicle_front_image');
        }

        if ($request->hasFile('back_image')) {
            $vehicle->addMediaFromRequest('back_image')->withCustomProperties(['type' => 'vehicle'])->toMediaCollection('vehicle_back_image');
        }

        if ($request->hasFile('right_side_image')) {
            $vehicle->addMediaFromRequest('right_side_image')->withCustomProperties(['type' => 'vehicle'])->toMediaCollection('vehicle_right_image');
        }

        if ($request->hasFile('left_side_image')) {
            $vehicle->addMediaFromRequest('left_side_image')->withCustomProperties(['type' => 'vehicle'])->toMediaCollection('vehicle_left_image');
        }
    }

    private function uploadRoadTaxImage(Vehicle $visitor, $request)
    {
        if ($request->hasFile('roadtax_image')) {
            $visitor->addMediaFromRequest('roadtax_image')->withCustomProperties(['type' => 'roadtax'])->toMediaCollection('roadtax_image');
        }
    }
}
