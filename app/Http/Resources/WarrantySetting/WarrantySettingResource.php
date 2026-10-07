<?php

namespace App\Http\Resources\WarrantySetting;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarrantySettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'warranty_handbook_url' => $this->warranty_handbook_url,
            'has_warranty_reminder' => $this->has_warranty_reminder,
            'has_appointment_schedule' => $this->has_appointment_schedule,
            'has_verification' => $this->has_verification,
        ];
    }
}
