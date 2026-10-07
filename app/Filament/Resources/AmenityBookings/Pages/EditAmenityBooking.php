<?php

namespace App\Filament\Resources\AmenityBookings\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\AmenityBookings\AmenityBookingResource;
use App\Models\ResidenceAmenity;
use App\Models\ResidenceAmenityOption;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAmenityBooking extends EditRecord
{
    protected static string $resource = AmenityBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

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

                $timeRange[] = $time->format('H:i') . ' - ' . $slotEnd->format('H:i');
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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        // Check if editing a past booking
        $record = $this->record ?? null;
        $isPastBooking = $record && $record->booking_date && now()->greaterThanOrEqualTo(Carbon::parse($record->booking_date));

        if (! $isPastBooking) {
            // Only run this for future bookings
            if (! empty($data['booking_date']) && ! empty($data['time_range'])) {
                sort($data['time_range']); // ensure order

                // first selected slot
                [$startTime, $ignore] = explode(' - ', $data['time_range'][0]);
                $start = Carbon::parse($data['booking_date'] . ' ' . $startTime)->format('Y-m-d H:i:s');

                // last selected slot
                [$ignore, $endTime] = explode(' - ', end($data['time_range']));
                $end = Carbon::parse($data['booking_date'] . ' ' . $endTime)->format('Y-m-d H:i:s');

                $data['start_at'] = $start;
                $data['end_at'] = $end;
            }

            // polymorphic bookingable fields
            if (! empty($data['amenity_sub_item_id'])) {
                $data['amenity_bookable_id'] = $data['amenity_sub_item_id'];
                $data['amenity_bookable_type'] = ResidenceAmenityOption::class;
            } elseif (! empty($data['amenity_id'])) {
                $data['amenity_bookable_id'] = $data['amenity_id'];
                $data['amenity_bookable_type'] = ResidenceAmenity::class;
            }
        }

        // Always unset unnecessary fields
        unset($data['residence_id'], $data['amenity_id'], $data['amenity_sub_item_id'], $data['time_range'], $data['booking_date']);

        return $data;
    }
}
