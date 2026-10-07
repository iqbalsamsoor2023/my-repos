<?php

namespace App\Http\Resources\Facility;

use Illuminate\Http\Resources\Json\ResourceCollection;

class FacilityBookingCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $data = $this->collection->map(function ($amenityBooking) {
            $facility = $amenityBooking->amenityBookable;
            $user = $amenityBooking->user;
            $unit = $amenityBooking->unit;

            return [
                'id' => $amenityBooking->id,
                'facility_id' => $amenityBooking->amenity_bookable_id,
                'user_id' => $amenityBooking->user_id,
                'unit_id' => $amenityBooking->unit_id,
                'ref_no' => $amenityBooking->ref_no,
                'start_at' => $amenityBooking->start_at,
                'end_at' => $amenityBooking->end_at,
                'status' => $amenityBooking->status,
                'created_by' => $amenityBooking->created_by,
                'updated_by' => $amenityBooking->updated_by,
                'created_at' => $amenityBooking->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $amenityBooking->updated_at?->format('Y-m-d H:i:s'),
                'deleted_at' => $amenityBooking->deleted_at?->format('Y-m-d H:i:s'),
                'facility' => $facility ? [
                    'id' => $facility->id,
                    'residence_id' => $facility->residence_id,
                    'name' => $facility->facilityAndAmenity?->name.' ('.$facility->facilityAndAmenity?->name_in_thai.')',
                    'booking_per_hour' => optional($facility->amenityTimeslots->first())->quota ?? 0,
                    'price_per_hour' => $facility->amenityRate?->price_per_hour ?? 0,
                    'price_per_day' => $facility->amenityRate?->price_per_day ?? 0,
                    'is_active' => $facility->is_active,
                    'created_at' => $facility->created_at?->format('Y-m-d H:i:s'),
                    'updated_at' => $facility->updated_at?->format('Y-m-d H:i:s'),
                    'deleted_at' => $facility->deleted_at?->format('Y-m-d H:i:s'),
                    'residence' => [
                        'id' => $facility?->residence?->id,
                        'name' => $facility?->residence?->name,
                        'name_th' => $facility?->residence?->name_th,
                    ],
                ] : null,
                'user' => $user ? [
                    'id' => $user->id,
                    'country_id' => $user?->country_id,
                    'name' => $user?->name,
                    'email' => $user?->email,
                    'id_number' => $user?->id_number,
                    'phone_no' => $user?->phone_no,
                    'address' => $user?->address,
                    'created_at' => $user?->created_at,
                    'updated_at' => $user?->updated_at,
                    'deleted_at' => $user?->deleted_at,
                ] : null,
                'unit' => $unit ? [
                    'id' => $unit->id,
                    'unit_number' => $unit?->unit_number,
                    'residence' => [
                        'id' => $unit?->residence?->id,
                        'name' => $unit?->residence?->name,
                    ],
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
