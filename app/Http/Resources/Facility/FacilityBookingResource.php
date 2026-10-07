<?php

namespace App\Http\Resources\Facility;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FacilityBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ref_no' => $this->ref_no,
            'facility_id' => $this->amenity_bookable_id,
            'user_id' => $this->user_id,
            'unit_id' => $this->unit_id,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }
}
