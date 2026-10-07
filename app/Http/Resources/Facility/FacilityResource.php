<?php

namespace App\Http\Resources\Facility;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FacilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'residence_id' => (int) $this->residence_id,
            'name' => $this->facilityAndAmenity?->name.' ('.$this->facilityAndAmenity?->name_in_thai.')',
            'booking_per_hour' => optional($this->amenityTimeslots->first())->quota ?? 0,
            'price_per_hour' => $this->amenityRate?->price_per_hour ?? 0,
            'price_per_day' => $this->amenityRate?->price_per_day ?? 0,
            'is_active' => 1,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'timeslots' => $this->amenityTimeslots->map(fn ($t) => [
                'id' => $t->id,
                'facility_id' => $t->amenity_timeslotable_id,
                'day' => $t->day,
                'start_at' => $t->start_at,
                'end_at' => $t->end_at,
                'is_active' => $t->is_active,
                'created_at' => $t->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $t->updated_at?->format('Y-m-d H:i:s'),
            ]),
        ];
    }
}
