<?php

namespace App\Http\Resources\Facility;

use App\Models\ResidenceAmenity;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FacilityCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $data = $this->collection
            ->filter(function ($amenity) {
                // Only include ResidenceAmenity that has NO options
                return $amenity->residenceAmenityOptions->isEmpty();
            })
            ->map(function ($amenity) {
                return [
                    'id' => $amenity->id,
                    'type' => 'residence-amenity',
                    'amenity_bookable_type' => ResidenceAmenity::class,
                    'residence_id' => $amenity->residence_id,
                    'amenity_id' => $amenity->facility_and_amenity_id,
                    'name' => $amenity->facilityAndAmenity?->name.' ('.$amenity->facilityAndAmenity?->name_in_thai.')',
                    'is_active' => $amenity->is_active,
                    'booking_per_hour' => optional($amenity->amenityTimeslots->first())->quota ?? 0,
                    'price_per_hour' => $amenity->amenityRate?->price_per_hour ?? 0,
                    'price_per_day' => $amenity->amenityRate?->price_per_day ?? 0,
                    'created_at' => $amenity->created_at?->format('Y-m-d H:i:s'),
                    'updated_at' => $amenity->updated_at?->format('Y-m-d H:i:s'),
                    'deleted_at' => $amenity->deleted_at?->format('Y-m-d H:i:s'),
                    'timeslots' => $amenity->amenityTimeslots->map(fn ($t) => [
                        'id' => $t->id,
                        'facility_id' => $t->amenity_timeslotable_id,
                        'day' => $t->day,
                        'start_at' => $t->start_at,
                        'end_at' => $t->end_at,
                        'is_active' => $t->is_active,
                        'created_at' => $t->created_at?->format('Y-m-d H:i:s'),
                        'updated_at' => $t->updated_at?->format('Y-m-d H:i:s'),
                        'deleted_at' => $t->deleted_at?->format('Y-m-d H:i:s'),
                    ])->values(),
                ];
            })->values();

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
