<?php

namespace App\Repositories;

use Exception;
use App\Actions\FacilityBooking\CheckBookingAvailabilityAction;
use App\Actions\FacilityBooking\GetFacilityBookingAction;
use App\Actions\FacilityBooking\UpdateFacilityBookingAction;
use App\Exceptions\GeneralException;
use App\Http\Requests\FacilityBooking\StoreFacilityBookingRequest;
use App\Http\Requests\FacilityBooking\UpdateFacilityBookingRequest;
use App\Interfaces\FacilityBookingRepositoryInterface;
use App\Models\AmenityBooking;
use App\Models\ResidenceAmenity;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacilityBookingRepository implements FacilityBookingRepositoryInterface
{
    public function index(Request $request)
    {
        $getFacilityBookingAction = new GetFacilityBookingAction;
        $facilityBooking = $getFacilityBookingAction->execute($request);

        return $facilityBooking;
    }

    public function create(StoreFacilityBookingRequest $request)
    {
        $startAt = Carbon::parse($request->start_at);
        $endAt = Carbon::parse($request->end_at);
        $dayOfWeek = $startAt->dayOfWeek; // Returns 0 (Sunday) to 6 (Saturday)

        $facility = ResidenceAmenity::with('amenityTimeslots')->findOrFail($request->facility_id);

        $booking_per_hour = optional($facility->amenityTimeslots->first())->quota ?? 0;

        $is_booking_availability = (new CheckBookingAvailabilityAction)->execute($request);

        if ($is_booking_availability >= $booking_per_hour) {
            throw new GeneralException(JsonResponse::HTTP_UNPROCESSABLE_ENTITY, 'Timeslot Not Available For Booking');
        }

        // Check if the selected time range matches any available facility timeslot
        $facilityTimeslot = $facility->amenityTimeslots
            ->filter(function ($timeslot) use ($startAt, $endAt, $dayOfWeek) {
                return (bool) $timeslot->is_active &&
                    (int) $timeslot->day === $dayOfWeek &&
                    $timeslot->start_at <= $startAt->format('H:i:s') &&
                    $timeslot->end_at >= $endAt->format('H:i:s');
            })
            ->first();

        if (! $facilityTimeslot) {
            throw new Exception('Selected time is not within the available timeslots', JsonResponse::HTTP_BAD_REQUEST);
        }

        $bookingCount = AmenityBooking::where('amenity_bookable_type', 'App\Models\ResidenceAmenity')
            ->where('amenity_bookable_id', $facility->id)
            ->whereDate('start_at', $startAt->toDateString())
            ->where(function ($query) use ($startAt, $endAt) {
                $query->whereBetween('start_at', [$startAt, $endAt])
                    ->orWhereBetween('end_at', [$startAt, $endAt])
                    ->orWhere(function ($q) use ($startAt, $endAt) {
                        $q->where('start_at', '<=', $startAt)
                            ->where('end_at', '>=', $endAt);
                    });
            })
            ->count();

        if ($bookingCount >= $facilityTimeslot->quota) {
            throw new Exception('This timeslot is fully booked', JsonResponse::HTTP_BAD_REQUEST);
        }

        $amenityBooking = AmenityBooking::create([
            'amenity_bookable_id' => $facility->id,
            'amenity_bookable_type' => ResidenceAmenity::class,
            'user_id' => $request->user_id,
            'unit_id' => $request->unit_id,
            'start_at' => $request->start_at,
            'end_at' => $request->end_at,
            'created_by' => $request->created_by,
        ]);

        if (! $amenityBooking) {
            throw new Exception('Failed to book amenity', JsonResponse::HTTP_BAD_REQUEST);
        }

        return $amenityBooking;
    }

    public function update(UpdateFacilityBookingRequest $request, int $id)
    {
        $facility_booking = AmenityBooking::findOrFail($id);
        $facilityBookingAction = new UpdateFacilityBookingAction;
        $facilityBooking = $facilityBookingAction->execute($request, $facility_booking);

        return $facilityBooking;
    }
}
