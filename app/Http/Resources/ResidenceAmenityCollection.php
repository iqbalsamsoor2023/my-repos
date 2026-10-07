<?php

namespace App\Http\Resources;

use App\Models\ResidenceAmenityOption;
use App\Models\ResidenceAmenity;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ResidenceAmenityCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $lang = $request->header('Accept-Language');
        $isThai = strtolower($lang) === 'th';

        $data = $this->collection->flatMap(function ($amenity) use ($isThai) {
            if ($amenity->residenceAmenityOptions->isNotEmpty()) {
                return $amenity->residenceAmenityOptions->map(function ($option) use ($amenity, $isThai) {
                    return [
                        'id' => $option->id,
                        'type' => 'residence-amenity-option',
                        'amenity_bookable_type' => ResidenceAmenityOption::class,
                        'residence_id' => $amenity->residence_id,
                        'amenity_id' => $amenity->facility_and_amenity_id,
                        'amenity_name' => $isThai ? $amenity->facilityAndAmenity?->name_in_thai : $amenity->facilityAndAmenity?->name,
                        'amenity_sub_name' => $isThai ? $option->name_in_thai : $option->name,
                        'icon_url' => $amenity->facilityAndAmenity?->icon_url,
                        'is_active' => $option->is_active,
                        'price_per_hour' => $option->amenityRate ? number_format($option->amenityRate->price_per_hour, 2, '.', '') : null,
                        'price_per_day' => $option->amenityRate ? number_format($option->amenityRate->price_per_day, 2, '.', '') : null,
                        'timeslots' => $option->amenityTimeslots->map(fn ($t) => [
                            'id' => $t->id,
                            'residence_amenity_id' => $t->amenity_timeslotable_id,
                            'quota' => $t->quota,
                            'day' => $t->day,
                            'start_at' => $t->start_at,
                            'end_at' => $t->end_at,
                            'is_active' => $t->is_active,
                        ])->values(),
                    ];
                });
            }

            return [[
                'id' => $amenity->id,
                'type' => 'residence-amenity',
                'amenity_bookable_type' => ResidenceAmenity::class,
                'residence_id' => $amenity->residence_id,
                'amenity_id' => $amenity->facility_and_amenity_id,
                'amenity_name' => $isThai ? $amenity->facilityAndAmenity?->name_in_thai : $amenity->facilityAndAmenity?->name,
                'amenity_sub_name' => null,
                'icon_url' => $amenity->facilityAndAmenity?->icon_url,
                'is_active' => $amenity->is_active,
                'price_per_hour' => $amenity->amenityRate ? number_format($amenity->amenityRate->price_per_hour, 2, '.', '') : null,
                'price_per_day' => $amenity->amenityRate ? number_format($amenity->amenityRate->price_per_day, 2, '.', '') : null,
                'timeslots' => $amenity->amenityTimeslots->map(fn ($t) => [
                    'id' => $t->id,
                    'residence_amenity_id' => $t->amenity_timeslotable_id,
                    'quota' => $t->quota,
                    'day' => $t->day,
                    'start_at' => $t->start_at,
                    'end_at' => $t->end_at,
                    'is_active' => $t->is_active,
                ])->values(),
            ]];
        })->values(); // ensure it's not a collection of collections

        return [
            'current_page' => $this->currentPage(),
            'data' => $data,
            'first_page_url' => $this->url(1),
            'from' => $this->firstItem(),
            'last_page' => $this->lastPage(),
            'last_page_url' => $this->url($this->lastPage()),
            'links' => $this->linkCollection()->toArray(),
            'next_page_url' => $this->nextPageUrl(),
            'path' => $this->path(),
            'per_page' => $this->perPage(),
            'prev_page_url' => $this->previousPageUrl(),
            'to' => $this->lastItem(),
            'total' => $this->total(),
        ];
    }
}
