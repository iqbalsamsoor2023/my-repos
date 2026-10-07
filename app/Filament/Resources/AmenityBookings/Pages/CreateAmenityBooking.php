<?php

namespace App\Filament\Resources\AmenityBookings\Pages;

use App\Filament\Resources\AmenityBookings\AmenityBookingResource;
use App\Models\ResidenceAmenity;
use App\Models\ResidenceAmenityOption;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;

class CreateAmenityBooking extends CreateRecord
{
    protected static string $resource = AmenityBookingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['booking_date']) && ! empty($data['time_range'])) {
            sort($data['time_range']); // ensure order

            $start = Carbon::parse($data['booking_date'].' '.$data['time_range'][0])->format('Y-m-d H:i:s');
            $end = Carbon::parse($data['booking_date'].' '.end($data['time_range']))->addHour()->format('Y-m-d H:i:s');

            $data['start_at'] = $start;
            $data['end_at'] = $end;
        }

        // Handle polymorphic bookingable fields
        if (! empty($data['amenity_sub_item_id'])) {
            $data['amenity_bookable_id'] = $data['amenity_sub_item_id'];
            $data['amenity_bookable_type'] = ResidenceAmenityOption::class;
        } else {
            $data['amenity_bookable_id'] = $data['amenity_id'];
            $data['amenity_bookable_type'] = ResidenceAmenity::class;
        }

        unset($data['residence_id'], $data['amenity_id'], $data['amenity_sub_item_id'], $data['time_range'], $data['booking_date']);

        return $data;
    }
}
