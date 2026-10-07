<?php

namespace App\Http\Resources\Vehicle;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = request()->header('Accept-Language');

        $response = [
            'id' => $this->id,
            'province_id' => (int) $this->province_id,
            'fuel_type' => (int) $this->fuel_type,
            'plate_number' => $this->plate_number,
            'policy_no' => $this->policy_no,
            'model_year' => $this->model_year,
            'insurance_expiry_date' => $this->insurance_expiry_date,
            'roadtax_expiry_date' => $this->roadtax_expiry_date,
            'lpr_image_url' => $this->lpr_image_url,
            'front_image_url' => $this->front_image_url,
            'right_side_image_url' => $this->right_side_image_url,
            'left_side_image_url' => $this->left_side_image_url,
            'back_image_url' => $this->back_image_url,
            'insurance_company' => [
                'id' => $this->insurance_company_id,
                'name' => $lang === 'th'
                    ? $this?->insuranceCompany?->name_th
                    : $this?->insuranceCompany?->name,
            ],
            'vehicle_model' => [
                'id' => $this?->vehicleModel?->id,
                'name' => $this?->vehicleModel?->name,
                'type' => (int) $this?->vehicleModel?->type,
                'body_type' => $this?->vehicleModel?->body_type,
                'vehicle_brand' => [
                    'id' => $this?->vehicleModel?->vehicleBrand?->id,
                    'name' => $lang === 'th'
                        ? $this?->vehicleModel?->vehicleBrand?->name_th
                        : $this?->vehicleModel?->vehicleBrand?->name,
                ],
            ],
            'province' => [
                'id' => $this?->province?->id,
                'name_in_english' => $this?->province?->name_in_english,
                'name_in_thai' => $this?->province?->name_in_thai,
            ],
        ];

        // to be removed after discontinuing v2 app support
        if (! $request->header('X-App-Version')) {
            $response['image_url'] = $this->image_url;
        }

        return $response;
    }
}
