<?php

namespace App\Http\Resources;

use App\Enums\Company\InsuranceTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InsuranceCompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_th' => $this->name_th,
            'type' => InsuranceTypeEnum::from($this->type)->label(), // returns "LIFE_INSURANCE"
            'website_url' => $this->website_url,
            'image_url' => $this->image_url,
            'is_active' => $this->is_active,
        ];
    }
}
