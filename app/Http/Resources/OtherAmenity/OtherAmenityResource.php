<?php

namespace App\Http\Resources\OtherAmenity;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OtherAmenityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'warranty_handbook_url' => $this->warranty_handbook_url,
        ];
    }
}
