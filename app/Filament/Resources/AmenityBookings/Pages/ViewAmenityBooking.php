<?php

namespace App\Filament\Resources\AmenityBookings\Pages;

use Carbon\Carbon;
use App\Filament\Resources\AmenityBookings\AmenityBookingResource;
use App\Models\ResidenceAmenity;
use App\Models\ResidenceAmenityOption;
use Filament\Resources\Pages\ViewRecord;

class ViewAmenityBooking extends ViewRecord
{
    protected static string $resource = AmenityBookingResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['residence_id'] = $this->record?->unit?->residence_id ?? null;

        if (! empty($data['start_at']) && ! empty($data['end_at'])) {
            $start = Carbon::parse($data['start_at']);
            $end = Carbon::parse($data['end_at']);

            $data['booking_date'] = $start->format('Y-m-d');

            $interval = 60; // minutes
            $timeRange = [];

            for ($time = $start->copy(); $time->lt($end); $time->addMinutes($interval)) {
                $slotEnd = $time->copy()->addMinutes($interval);
                if ($slotEnd->gt($end)) {
                    break;
                }

                $timeRange[] = $time->format('H:i').' - '.$slotEnd->format('H:i');
            }

            $data['time_range'] = $timeRange;
        }

        if (! empty($data['amenity_bookable_type']) && ! empty($data['amenity_bookable_id'])) {
            if ($data['amenity_bookable_type'] === ResidenceAmenity::class) {
                $data['amenity_id'] = $data['amenity_bookable_id'];
                $data['amenity_sub_item_id'] = null;
            } elseif ($data['amenity_bookable_type'] === ResidenceAmenityOption::class) {
                $data['amenity_id'] = optional(ResidenceAmenityOption::find($data['amenity_bookable_id']))->residence_amenity_id;
                $data['amenity_sub_item_id'] = $data['amenity_bookable_id'];
            } else {
                $data['amenity_id'] = null;
                $data['amenity_sub_item_id'] = null;
            }
        }

        return $data;
    }
}
