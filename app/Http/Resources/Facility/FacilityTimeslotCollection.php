<?php

namespace App\Http\Resources\Facility;

use Illuminate\Http\Resources\Json\ResourceCollection;

class FacilityTimeslotCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $data = $this->collection->map(function ($amenity) {
            $facility = $amenity->amenityTimeslotable;

            return [
                'id' => $amenity->id,
                'facility_id' => $amenity->amenity_timeslotable_id,
                'day' => $amenity->day,
                'start_at' => $amenity->start_at,
                'end_at' => $amenity->end_at,
                'is_active' => $amenity->is_active,
                'created_at' => $amenity->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $amenity->updated_at?->format('Y-m-d H:i:s'),
                'deleted_at' => $amenity->deleted_at?->format('Y-m-d H:i:s'),
                'facility' => $facility ? [
                    'id' => $facility->id,
                    'residence_id' => $facility->residence_id,
                    'name' => $facility->facilityAndAmenity?->name,
                    'name_th' => $facility->facilityAndAmenity?->name_in_thai,
                    'booking_per_hour' => optional($facility->amenityTimeslots->first())->quota ?? 0,
                    'price_per_hour' => $facility->amenityRate?->price_per_hour ?? 0,
                    'price_per_day' => $facility->amenityRate?->price_per_day ?? 0,
                    'is_active' => $facility->is_active,
                    'created_at' => $facility->created_at?->format('Y-m-d H:i:s'),
                    'updated_at' => $facility->updated_at?->format('Y-m-d H:i:s'),
                    'deleted_at' => $facility->deleted_at?->format('Y-m-d H:i:s'),
                ] : null,
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
