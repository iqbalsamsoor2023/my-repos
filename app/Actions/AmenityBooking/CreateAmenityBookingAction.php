<?php

namespace App\Actions\AmenityBooking;

use Exception;
use App\Filament\Resources\AmenityBookings\AmenityBookingResource;
use App\Http\Requests\AmenityBooking\StoreAmenityBookingRequest;
use App\Models\AmenityBooking;
use App\Models\Residence;
use App\Models\ResidenceAmenity;
use App\Models\ResidenceAmenityOption;
use App\Models\User;
use App\Notifications\AmenityBookingCreated;
use App\Support\Notifications\DashboardNotification;
use Carbon\Carbon;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\JsonResponse;

class CreateAmenityBookingAction
{
    public function execute(StoreAmenityBookingRequest $request): AmenityBooking
    {
        $data = $request->validated();

        if ($data['amenity_bookable_type'] === ResidenceAmenityOption::class) {
            $residenceAmenityOption = ResidenceAmenityOption::with('amenityTimeslots')->find($data['amenity_bookable_id']);

            $start = Carbon::parse($data['start_at']);
            $end = Carbon::parse($data['end_at']);
            $dayOfWeek = $start->dayOfWeek; // Returns 0 (Sunday) to 6 (Saturday)

            $availableTimeslot = $residenceAmenityOption->amenityTimeslots
                ->filter(function ($timeslot) use ($start, $end, $dayOfWeek) {
                    return (bool) $timeslot->is_active &&
                        (int) $timeslot->day === $dayOfWeek &&
                        $timeslot->start_at <= $start->format('H:i:s') &&
                        $timeslot->end_at >= $end->format('H:i:s');
                })
                ->first();

            if (! $availableTimeslot) {
                throw new Exception('Selected time is not within the available timeslots', JsonResponse::HTTP_BAD_REQUEST);
            }

            $bookingCount = AmenityBooking::where('amenity_bookable_type', $data['amenity_bookable_type'])
                ->where('amenity_bookable_id', $data['amenity_bookable_id'])
                ->whereDate('start_at', $start->toDateString())
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('start_at', [$start, $end])
                        ->orWhereBetween('end_at', [$start, $end])
                        ->orWhere(function ($q) use ($start, $end) {
                            $q->where('start_at', '<=', $start)
                                ->where('end_at', '>=', $end);
                        });
                })
                ->count();

            if ($bookingCount >= $availableTimeslot->quota) {
                throw new Exception('This timeslot is fully booked', JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        $amenityBooking = AmenityBooking::create($data);

        if (! $amenityBooking) {
            throw new Exception('Failed to book amenity', JsonResponse::HTTP_BAD_REQUEST);
        }

        $this->sendAmenityBookingCreatedNotification($amenityBooking);

        return $amenityBooking;
    }

    private function sendAmenityBookingCreatedNotification($amenityBooking)
    {
        $residence = Residence::whereId($amenityBooking?->unit?->residence_id)->firstOrFail();
        $pm_user = User::findOrFail($residence->property_management_user_id);
        $pm_user->notify(new AmenityBookingCreated($amenityBooking));

        // send notification to PM dashboard
        $recipient = $pm_user;

        $start = Carbon::parse($amenityBooking->start_at);
        $end = Carbon::parse($amenityBooking->end_at);

        $params = [
            'residentName' => $amenityBooking->user?->name,
            'unit' => $amenityBooking->unit?->unit_number,
            'date' => $start->format('d/m/Y'),
            'time' => $start->format('H:i').' - '.$end->format('H:i'),
        ];

        DashboardNotification::make('notification.amenity_booking_created.title')
            ->params($params)
            ->localizedParams(['facility' => $this->resolveFacilityName($amenityBooking->amenityBookable)])
            ->icon(Heroicon::OutlinedCalendarDays, 'info')
            ->viewAction(AmenityBookingResource::getUrl('view', ['record' => $amenityBooking->id]))
            ->sendToDatabase($recipient);
    }

    /**
     * Resolve the booked amenity/facility name in both English and Thai.
     *
     * @return array{en: ?string, th: ?string}
     */
    private function resolveFacilityName($bookable): array
    {
        if ($bookable instanceof ResidenceAmenityOption) {
            return [
                'en' => $bookable->name,
                'th' => $bookable->name_in_thai ?: $bookable->name,
            ];
        }

        if ($bookable instanceof ResidenceAmenity) {
            $facility = $bookable->facilityAndAmenity;

            return [
                'en' => $facility?->name,
                'th' => $facility?->name_in_thai ?: $facility?->name,
            ];
        }

        return ['en' => null, 'th' => null];
    }
}
