<?php

namespace App\Http\Resources;

use App\Models\ResidenceAmenityOption;
use App\Models\ResidenceAmenity;
use Illuminate\Http\Resources\Json\JsonResource;

class ResidenceAmenityResource extends JsonResource
{
    public function toArray($request)
    {
        $lang = $request->header('Accept-Language');
        $isThai = strtolower($lang) === 'th';

        $isOption = $this->relationLoaded('residenceAmenity');

        // Set data source based on model type
        $residenceAmenity = $isOption ? $this->residenceAmenity : $this;
        $facilityAndAmenity = $residenceAmenity?->facilityAndAmenity;

        return [
            'id' => $this->id,
            'type' => $isOption ? ResidenceAmenityOption::class : ResidenceAmenity::class,
            'amenity_name' => $isOption
                ? ($isThai ? $this->name_in_thai : $this->name)
                : ($isThai ? $facilityAndAmenity?->name_in_thai : $facilityAndAmenity?->name),
            'residence_id' => $residenceAmenity?->residence_id,
            'price_per_hour' => $this->amenityRate ? number_format($this->amenityRate->price_per_hour, 2, '.', '') : null,
            'price_per_day' => $this->amenityRate ? number_format($this->amenityRate->price_per_day, 2, '.', '') : null,
            'timeslots' => $this->amenityTimeslots->map(function ($timeslot) {
                return [
                    'id' => $timeslot->id,
                    'quota' => $timeslot->quota,
                    'day' => $timeslot->day,
                    'start_at' => $timeslot->start_at,
                    'end_at' => $timeslot->end_at,
                ];
            }),
            'timeslot_intervals' => $this->timeslot_intervals ?? [],
        ];
    }
}
