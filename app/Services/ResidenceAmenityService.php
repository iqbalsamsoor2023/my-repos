<?php

namespace App\Services;

use App\Models\ResidenceAmenityOption;
use App\Models\ResidenceAmenity;
use InvalidArgumentException;
use App\Actions\ResidenceAmenity\GetResidenceAmenityAction;
use App\Actions\ResidenceAmenity\GetResidenceAmenityByIdAction;
use App\Enums\FacilityAndAmenity\AmenityBookingStatusEnum;
use App\Models\AmenityBooking;
use App\Models\AmenityTimeslot;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class ResidenceAmenityService
{
    public function index(Request $request)
    {
        $getResidenceAmenityAction = new GetResidenceAmenityAction;
        $residenceAmenities = $getResidenceAmenityAction->execute($request);

        return $residenceAmenities;
    }

    public function show(string $modelType, int $id, ?string $date = null, ?int $userId = null)
    {
        $modelClass = $this->resolveModelClass($modelType);
        $residenceAmenity = (new GetResidenceAmenityByIdAction)->execute($id, $modelClass);

        if ($date && $userId) {
            if (Carbon::parse($date)->isToday() || Carbon::parse($date)->isFuture()) {
                $slots = $this->generateSlots($residenceAmenity, $date, $userId);
                $residenceAmenity->timeslot_intervals = $slots;
            } else {
                $residenceAmenity->timeslot_intervals = [];
            }
        }

        return $residenceAmenity;
    }

    protected function resolveModelClass(string $modelType): string
    {
        return match ($modelType) {
            'residence-amenity-option' => ResidenceAmenityOption::class,
            'residence-amenity' => ResidenceAmenity::class,
            default => throw new InvalidArgumentException('Invalid Model Type'),
        };
    }

    protected function generateSlots($model, $date, ?int $userId = null)
    {
        App::setLocale(request()->header('Accept-Language', 'en-US'));

        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $modelType = get_class($model);
        $modelId = $model->id;

        $timeslot = AmenityTimeslot::where('amenity_timeslotable_id', $modelId)
            ->where('amenity_timeslotable_type', $modelType)
            ->where('day', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (! $timeslot) {
            return [];
        }

        $start = Carbon::createFromTimeString($timeslot->start_at);
        $end = Carbon::createFromTimeString($timeslot->end_at);
        $interval = 60; // 1 hour
        $slots = [];

        $now = Carbon::now();
        $isToday = Carbon::parse($date)->isToday();

        while ($start->lt($end)) {
            $slotStart = Carbon::createFromFormat('Y-m-d H:i', "$date ".$start->format('H:i'));
            $slotEnd = $slotStart->copy()->addMinutes($interval);

            if ($slotEnd->gt(Carbon::createFromFormat('Y-m-d H:i', "$date ".$end->format('H:i')))) {
                break;
            }

            if ($isToday && $slotEnd->lte($now)) {
                // Slot is in the past and today
                $slots[] = [
                    'label' => $slotStart->format('H:i').' - '.$slotEnd->format('H:i'),
                    'start_at' => $slotStart->format('H:i'),
                    'end_at' => $slotEnd->format('H:i'),
                    'status' => __('app.out_of_time'),
                    'color' => 'gray',
                ];
            } else {
                $bookingQuery = AmenityBooking::whereDate('start_at', $date)
                    ->where('amenity_bookable_id', $modelId)
                    ->where('amenity_bookable_type', $modelType)
                    ->where('status', AmenityBookingStatusEnum::BOOKED->value)
                    ->where(function ($query) use ($slotStart, $slotEnd) {
                        $query->where('start_at', '<', $slotEnd)
                            ->where('end_at', '>', $slotStart);
                    });

                $bookingCount = (clone $bookingQuery)->count();

                // Check if current user has booked this slot
                $userHasBooked = false;
                if ($userId) {
                    $userHasBooked = (clone $bookingQuery)->where('user_id', $userId)->exists();
                }

                if ($userHasBooked) {
                    $status = __('app.already_booked');
                    $color = 'green';
                } elseif ($bookingCount >= $timeslot->quota) {
                    $status = __('app.full');
                    $color = 'red';
                } else {
                    $status = __('app.slot_remaining', ['count' => ($timeslot->quota - $bookingCount)]);
                    $color = 'blue';
                }

                $slots[] = [
                    'label' => $slotStart->format('H:i').' - '.$slotEnd->format('H:i'),
                    'start_at' => $slotStart->format('H:i'),
                    'end_at' => $slotEnd->format('H:i'),
                    'status' => $status,
                    'color' => $color,
                ];
            }

            $start->addMinutes($interval);
        }

        return $slots;
    }
}
