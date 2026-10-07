<?php

namespace App\Filament\Resources\AmenityBookings\Schemas;

use App\Enums\FacilityAndAmenity\AmenityBookingStatusEnum;
use App\Enums\FacilityAndAmenity\FacilityAmenityTypeEnum;
use App\Enums\GeneralSwitch;
use App\Enums\User\RoleType;
use App\Filament\Resources\AmenityBookings\Pages\CreateAmenityBooking;
use App\Filament\Resources\AmenityBookings\Pages\EditAmenityBooking;
use App\Forms\Components\AmenityBooking\DateTimePicker;
use App\Models\AmenityBooking;
use App\Models\AmenityTimeslot;
use App\Models\ResidenceAmenity;
use App\Models\ResidenceAmenityOption;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class AmenityBookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.amenity_booking'))
                    ->description(__('app.amenity_booking_detail'))
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make(__('app.details'))
                            ->schema([
                                Select::make('residence_id')
                                    ->label(__('app.mooban_or_residence'))
                                    ->options(function (Component $livewire) {
                                        if ($livewire instanceof CreateAmenityBooking) {
                                            return list_create_residences();
                                        }

                                        return list_residences();
                                    })
                                    ->reactive()
                                    ->searchable()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('amenity_id', null);
                                        $set('unit_id', null);
                                    })
                                    ->required(),
                                Select::make('unit_id')
                                    ->label(__('unit.unit_number'))
                                    ->options(function (callable $get) {
                                        return Unit::where('residence_id', $get('residence_id'))->pluck('unit_number', 'id')->toArray();
                                    })
                                    ->preload()
                                    ->reactive()
                                    ->searchable()
                                    ->afterStateUpdated(function (Component $livewire, callable $set, $state, $old) {
                                        if ($livewire instanceof EditAmenityBooking && $state != $old) {
                                            $set('time_range', null);
                                        }
                                    })
                                    ->required(),
                                Select::make('user_id')
                                    ->label(__('app.resident'))
                                    ->options(function (callable $get) {
                                        return User::whereHas('roles', function ($query) {
                                            $query->whereIn('name', [RoleType::UNIT_OWNER->value, RoleType::UNIT_TENANT->value]);
                                        })
                                            ->whereHas('units', function (Builder $builder) use ($get) {
                                                return $builder->where('unit_user.unit_id', $get('unit_id'));
                                            })->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->required(),
                                Select::make('amenity_id')
                                    ->label(__('app.amenity'))
                                    ->options(function (callable $get) {
                                        return ResidenceAmenity::where('residence_id', $get('residence_id'))
                                            ->where('is_active', GeneralSwitch::ON->value)
                                            ->whereHas('facilityAndAmenity', function ($query) {
                                                $query->where('type', FacilityAmenityTypeEnum::AMENITY->value);
                                            })
                                            ->get()
                                            ->mapWithKeys(function ($item) {
                                                return [
                                                    $item->id => "{$item?->facilityAndAmenity->name} ({$item?->facilityAndAmenity->name_in_thai})",
                                                ];
                                            })
                                            ->toArray();
                                    })
                                    ->preload()
                                    ->reactive()
                                    ->searchable()
                                    ->required()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('amenity_sub_item_id', null);
                                        $set('booking_date', null);
                                        $set('time_range', null);
                                    }),
                                Select::make('amenity_sub_item_id')
                                    ->label(__('app.amenity_sub_item'))
                                    ->options(
                                        fn(Get $get) => optional(ResidenceAmenity::find($get('amenity_id')))
                                            ->residenceAmenityOptions
                                            ->mapWithKeys(fn($option) => [
                                                $option->id => "{$option->name} ({$option->name_in_thai})",
                                            ]) ?? []
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->required()
                                    ->hidden(
                                        fn(Get $get) => empty($get('amenity_id')) ||
                                            optional(ResidenceAmenity::find($get('amenity_id')))->residenceAmenityOptions->isEmpty()
                                    ),
                                DateTimePicker::make('booking_date')
                                    ->label(__('app.booking_date'))
                                    ->required()
                                    ->native(false)
                                    ->seconds(false)
                                    ->minDate(function (Component $livewire) {
                                        $record = $livewire->getRecord();
                                                    
                                        // If updating status to COMPLETE, skip minDate
                                        if (!is_null($record) && (int) $livewire->data['status'] === AmenityBookingStatusEnum::COMPLETE->value) {
                                            return null;
                                        }
                                
                                        return today();
                                    })
                                    ->reactive()
                                    ->disabledDates(function (Component $livewire, $component, callable $get) {
                                        $subItemId = $get('amenity_sub_item_id');
                                        $amenityId = $get('amenity_id');

                                        if (! $subItemId && ! $amenityId) {
                                            return [];
                                        }

                                        $modelType = $subItemId ? ResidenceAmenityOption::class : ResidenceAmenity::class;
                                        $modelId = $subItemId ?: $amenityId;

                                        $dayslots = AmenityTimeslot::where('amenity_timeslotable_id', $modelId)
                                            ->where('amenity_timeslotable_type', $modelType)
                                            ->where('is_active', true)
                                            ->pluck('day')
                                            ->unique()
                                            ->toArray();

                                        // Focused month for current calendar view
                                        $currentMonth = $component->getFocusedMonth();

                                        $startOfMonth = now()->setMonth($currentMonth)->startOfMonth();
                                        $endOfMonth = $startOfMonth->copy()->endOfMonth();

                                        $disabledDates = [];

                                        while ($startOfMonth->lte($endOfMonth)) {
                                            if (! in_array($startOfMonth->dayOfWeek, $dayslots)) {
                                                $disabledDates[] = $startOfMonth->format('Y-m-d');
                                            }

                                            $startOfMonth->addDay();
                                        }

                                        return $disabledDates;
                                    })
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('time_range', null);
                                    }),
                                Select::make('time_range')
                                    ->label(__('app.select_time_range'))
                                    ->searchable(false)
                                    ->multiple()
                                    ->reactive()
                                    ->required()
                                    ->options(function (callable $get) {
                                        $bookingDate = $get('booking_date');
                                        $subItemId = $get('amenity_sub_item_id');
                                        $amenityId = $get('amenity_id');

                                        if (! $bookingDate || (! $subItemId && ! $amenityId)) {
                                            return [];
                                        }

                                        $selectedRanges = collect((array) $get('time_range'))
                                            ->map(fn ($v) => trim($v))
                                            ->toArray();

                                        $date = Carbon::parse($bookingDate);
                                        $dayOfWeek = $date->dayOfWeek;

                                        // Determine polymorphic target
                                        $modelType = $subItemId ? ResidenceAmenityOption::class : ResidenceAmenity::class;
                                        $modelId = $subItemId ?: $amenityId;

                                        $timeslot = AmenityTimeslot::where('amenity_timeslotable_id', $modelId)
                                            ->where('amenity_timeslotable_type', $modelType)
                                            ->where('day', $dayOfWeek)
                                            ->where('is_active', true)
                                            ->first();

                                        if (! $timeslot) {
                                            return [];
                                        }

                                        $bookingDateFormatted = Carbon::parse($bookingDate)->format('Y-m-d');

                                        $start = Carbon::createFromTimeString($timeslot->start_at);
                                        $end = Carbon::createFromTimeString($timeslot->end_at);

                                        $slots = [];

                                        $interval = 60; // in minutes (1 hour)
                                        while ($start->lt($end)) {
                                            $slotStart = Carbon::createFromFormat('Y-m-d H:i', "$bookingDateFormatted " . $start->format('H:i'));
                                            $slotEnd = $slotStart->copy()->addMinutes($interval);

                                            if ($slotEnd->gt(Carbon::createFromFormat('Y-m-d H:i', "$bookingDateFormatted " . $end->format('H:i')))) {
                                                break;
                                            }

                                            // Skip if time has already passed today
                                            if ($bookingDateFormatted === now()->format('Y-m-d') && $slotStart->lt(now())) {
                                                $start->addMinutes($interval);

                                                continue;
                                            }

                                            $existingBookings = AmenityBooking::whereDate('start_at', $bookingDateFormatted)
                                                ->where('amenity_bookable_id', $modelId)
                                                ->where('amenity_bookable_type', $modelType)
                                                ->where(function ($query) use ($slotStart, $slotEnd) {
                                                    $query->where(function ($q) use ($slotStart, $slotEnd) {
                                                        $q->where('start_at', '<', $slotEnd)
                                                            ->where('end_at', '>', $slotStart);
                                                    });
                                                })
                                                ->count();

                                            if ($existingBookings < $timeslot->quota) {
                                                $value = $slotStart->format('H:i').' - '.$slotEnd->format('H:i');

                                                if (! in_array($value, $selectedRanges, true)) {
                                                    $slots[$value] =
                                                        $slotStart->format('g:i A')
                                                        .' - '.
                                                        $slotEnd->format('g:i A');
                                                }
                                            }

                                            $start->addMinutes($interval);
                                        }

                                        // Merge in already-selected time_range values (for edit mode)
                                        $selected = $get('time_range') ?? [];

                                        foreach ((array) $selected as $selectedTime) {
                                            try {
                                                [$startTime, $endTime] = explode(' - ', $selectedTime);

                                                $slotStart = Carbon::createFromFormat('Y-m-d H:i', "$bookingDateFormatted $startTime");
                                                $slotEnd = Carbon::createFromFormat('Y-m-d H:i', "$bookingDateFormatted $endTime");

                                                $value = $startTime . ' - ' . $endTime;
                                                $label = $slotStart->format('g:i A') . ' - ' . $slotEnd->format('g:i A');

                                                $slots[$value] = $label;
                                            } catch (\Exception $e) {
                                                continue;
                                            }
                                        }

                                        ksort($slots); // keep the list sorted chronologically

                                        return $slots;
                                    })
                                    ->helperText(function (callable $get) {
                                        $bookingDate = $get('booking_date');
                                        $subItemId = $get('amenity_sub_item_id');
                                        $amenityId = $get('amenity_id');

                                        if (! $bookingDate || (! $subItemId && ! $amenityId)) {
                                            return null;
                                        }

                                        $date = Carbon::parse($bookingDate);
                                        $bookingDateFormatted = $date->format('Y-m-d');
                                        $dayOfWeek = $date->dayOfWeek;

                                        $modelType = $subItemId ? ResidenceAmenityOption::class : ResidenceAmenity::class;
                                        $modelId = $subItemId ?: $amenityId;

                                        $timeslot = AmenityTimeslot::where('amenity_timeslotable_id', $modelId)
                                            ->where('amenity_timeslotable_type', $modelType)
                                            ->where('day', $dayOfWeek)
                                            ->where('is_active', true)
                                            ->first();

                                        if (! $timeslot) {
                                            return __('No timeslots are available on this day.');
                                        }

                                        $start = Carbon::createFromTimeString($timeslot->start_at);
                                        $end = Carbon::createFromTimeString($timeslot->end_at);
                                        $hasAvailableSlot = false;

                                        while ($start->lt($end)) {
                                            $timeKey = $start->format('H:i');
                                            $slotStart = Carbon::createFromFormat('Y-m-d H:i', "$bookingDateFormatted $timeKey");
                                            $slotEnd = $slotStart->copy()->addHour();

                                            $existingBookings = AmenityBooking::whereDate('start_at', $bookingDateFormatted)
                                                ->where('amenity_bookable_id', $modelId)
                                                ->where('amenity_bookable_type', $modelType)
                                                ->where(function ($query) use ($slotStart, $slotEnd) {
                                                    $query->whereBetween('start_at', [$slotStart, $slotEnd->copy()->subSecond()])
                                                        ->orWhereBetween('end_at', [$slotStart->copy()->addSecond(), $slotEnd]);
                                                })
                                                ->count();

                                            if ($existingBookings < $timeslot->quota) {
                                                $hasAvailableSlot = true;
                                                break;
                                            }

                                            $start->addHour();
                                        }

                                        return $hasAvailableSlot ? null : __('Fully booked.');
                                    }),
                                Hidden::make('created_by')
                                    ->default(auth()->user()->id),
                            ])
                            ->disabled(function (Component $livewire): bool {
                                if (! $livewire instanceof EditAmenityBooking) {
                                    return false;
                                }

                                $record = $livewire->getRecord();

                                if (! $record || ! $record->start_at) {
                                    return false;
                                }

                                return now()->greaterThanOrEqualTo(Carbon::parse($record->start_at));
                            })
                            ->columns(3),
                        Select::make('status')
                            ->label(__('app.status'))
                            ->visible(fn(Component $livewire): bool => $livewire instanceof EditAmenityBooking)
                            ->options(function (Component $livewire) {
                                $record = $livewire->getRecord();

                                if (! $record || ! $record->start_at) {
                                    return AmenityBookingStatusEnum::options(); // Default to showing all options
                                }

                                $startAt = Carbon::parse($record->start_at);

                                if (now()->lessThan($startAt)) {
                                    return AmenityBookingStatusEnum::options(); // Before booking start time — show all options
                                }

                                // After booking start time — limit to BOOKED and COMPLETE
                                return [
                                    AmenityBookingStatusEnum::BOOKED->value => AmenityBookingStatusEnum::BOOKED->getLabel(),
                                    AmenityBookingStatusEnum::COMPLETE->value => AmenityBookingStatusEnum::COMPLETE->getLabel(),
                                ];
                            })
                            ->searchable()
                            ->required()
                    ]),

            ]);
    }
}
