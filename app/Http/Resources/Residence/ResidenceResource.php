<?php

namespace App\Http\Resources\Residence;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResidenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $response = [
            'id' => $this->id,
            'name' => $this->name,
            'name_th' => $this->name_th,
            'latitude' => $this->latitude, 
            'longitude' => $this->longitude,
            'avatar_url' => $this?->propertyManagementUser?->profile_image_url,
            'logo_url' => $this->logo_url,
            'image_url' => $this->image_url,
            'property_management_user' => [
                'profile_image_url' => $this?->propertyManagementUser?->profile_image_url,
                'pmoc_type' => $this?->propertyManagementUser?->sub_type
            ],
            'developer' => [
                'image_url' => $this?->developer?->image_url,
            ]
        ];

        return $response;
    }
}
