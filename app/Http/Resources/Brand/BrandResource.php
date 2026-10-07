<?php

namespace App\Http\Resources\Brand;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'country_id' => $this->country_id,
            'name' => $this->name,
            'name_th' => $this->name_th,
            'gpl_priority' => $this->gpl_priority,
            'image_url' => $this->image_url,
        ];
    }
}
