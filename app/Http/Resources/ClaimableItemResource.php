<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Illuminate\Http\Resources\Json\JsonResource;

class ClaimableItemResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'residence_id' => $this->residence_id,
            'item' => $this->facilityAndAmenity?->name.' ('.$this->facilityAndAmenity?->name_in_thai.')',
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
            'moobaan' => [
                'id' => $this?->residence?->id,
                'name' => $this?->residence?->name,
                'name_th' => $this?->residence?->name_th,
            ],
        ];
    }
}
