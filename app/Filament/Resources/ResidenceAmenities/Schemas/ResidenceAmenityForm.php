<?php

namespace App\Filament\Resources\ResidenceAmenities\Schemas;

use App\Enums\FacilityAndAmenity\DayOfWeekEnum;
use App\Enums\FacilityAndAmenity\FacilityAmenityTypeEnum;
use App\Models\FacilityAndAmenity;
use App\Models\Residence;
use App\Models\ResidenceAmenity;
use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\App;
use Livewire\Component;

class ResidenceAmenityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('residence.amenities_or_facilities'))
                    ->columns(5)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('residence_id')
                            ->label(__('app.mooban_or_residence'))
                            ->options(function () {
                                $user = auth()->user();

                                return $user->hasRole('Property Management')
                                    ? Residence::where('property_management_user_id', $user->id)->pluck('name', 'id')->toArray()
                                    : Residence::pluck('name', 'id')->toArray();
                            })
                            ->searchable()
                            ->required(),
                        Select::make('facility_and_amenity_id')
                            ->label(__('app.amenity_or_facility_category'))
                            ->options(function ($get) {
                                $residenceId = $get('residence_id');
                                $currentId = $get('facility_and_amenity_id');

                                if (! $residenceId) {
                                    return [];
                                }

                                // Get all used amenity IDs that are bookable or claimable
                                $usedIdsQuery = ResidenceAmenity::where('residence_id', $residenceId)
                                    ->where(function ($query) {
                                        $query->where('is_bookable', true)
                                            ->orWhere('is_claimable', true);
                                    });

                                if ($currentId) {
                                    $usedIdsQuery->where('facility_and_amenity_id', '!=', $currentId);
                                }

                                $usedIds = $usedIdsQuery->pluck('facility_and_amenity_id')->toArray();

                                return ResidenceAmenity::where('residence_id', $residenceId)
                                    ->whereNotIn('facility_and_amenity_id', $usedIds)
                                    ->with('facilityAndAmenity')
                                    ->get()
                                    ->filter(fn($item) => $item->facilityAndAmenity && $item->facilityAndAmenity->is_active)
                                    ->mapWithKeys(function ($item) {
                                        $label = App::getLocale() === 'th'
                                            ? $item->facilityAndAmenity->name_in_thai
                                            : $item->facilityAndAmenity->name;

                                        return [$item->facility_and_amenity_id => $label];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->reactive()
                            ->required(),
                        Toggle::make('is_bookable')
                            ->inline(false)
                            ->label(__('app.is_bookable'))
                            ->default(true)
                            ->reactive()
                            ->required()
                            ->visible(function (callable $get) {
                                $facilityAndAmenityId = $get('facility_and_amenity_id');

                                if (! $facilityAndAmenityId) {
                                    return false;
                                }

                                $record = FacilityAndAmenity::find($facilityAndAmenityId);

                                return $record?->type !== FacilityAmenityTypeEnum::FACILITY->value;
                            }),
                        Toggle::make('is_claimable')
                            ->inline(false)
                            ->label(__('app.is_claimable'))
                            ->default(false)
                            ->reactive()
                            ->required(),
                        Toggle::make('has_subamenities')
                            ->inline(false)
                            ->label(__('app.has_sub_amenities'))
                            ->helperText(__('app.enable_to_add_sub_room_name'))
                            ->default(false)
                            ->reactive()
                            ->required()
                            ->visible(function (callable $get) {
                                $facilityAndAmenityId = $get('facility_and_amenity_id');
                                $isBookable = $get('is_bookable');

                                if (! $facilityAndAmenityId) {
                                    return false;
                                }

                                $record = FacilityAndAmenity::find($facilityAndAmenityId);

                                return $record?->type !== FacilityAmenityTypeEnum::FACILITY->value && $isBookable === true;
                            }),

                        Fieldset::make('Bookings & Pricing')
                            ->schema([
                                TextInput::make('price_per_hour')
                                    ->label(__('app.price_per_hour'))
                                    ->stripCharacters(',')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->prefix('฿')
                                    ->required()
                                    ->afterStateHydrated(function ($component, $state, $record) {
                                        $component->state($record?->amenityRate?->price_per_hour);
                                    }),
                                TextInput::make('price_per_day')
                                    ->label(__('app.price_per_day'))
                                    ->prefix('฿')
                                    ->stripCharacters(',')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->required()
                                    ->afterStateHydrated(function ($component, $state, $record) {
                                        $component->state($record?->amenityRate?->price_per_day);
                                    }),
                            ])
                            ->columns(2)
                            ->columnSpanFull()
                            ->reactive()
                            ->visible(function (callable $get) {
                                $facilityAndAmenityId = $get('facility_and_amenity_id');
                                $isBookable = $get('is_bookable');
                                $hasSubAmenities = $get('has_subamenities');

                                if (! $facilityAndAmenityId) {
                                    return false;
                                }

                                $record = FacilityAndAmenity::find($facilityAndAmenityId);

                                return $record?->type === FacilityAmenityTypeEnum::AMENITY->value && $hasSubAmenities === false && $isBookable === true;
                            }),


                        Fieldset::make(__('app.timeslots'))
                            ->columnSpanFull()
                            ->schema([
                                Repeater::make('timeslots')
                                    ->label('')
                                    ->schema([
                                        Select::make('day')
                                            ->label(__('app.day'))
                                            ->options(function (callable $get) {
                                                $allDays = DayOfWeekEnum::options();
                                                $timeslots = $get('../../timeslots') ?? [];
                                                $currentIndex = $get('__index');

                                                $selectedDays = collect($timeslots)
                                                    ->pluck('day')
                                                    ->filter(fn($day) => $day !== null && $day !== '')
                                                    ->map(fn($d) => (string) $d)
                                                    ->all();

                                                $currentDay = isset($timeslots[$currentIndex]['day']) ? (string) $timeslots[$currentIndex]['day'] : null;

                                                return collect($allDays)->reject(function ($label, $value) use ($selectedDays, $currentDay) {
                                                    return in_array((string) $value, $selectedDays) && (string) $value !== $currentDay;
                                                })->toArray();
                                            })
                                            ->getOptionLabelUsing(fn($value) => DayOfWeekEnum::options()[(string) $value] ?? '-')
                                            ->rules([
                                                function (Component $livewire) {
                                                    return function (string $attribute, $value, Closure $fail) use ($livewire) {
                                                        foreach ($livewire->data['timeslots'] as $timeslot) {
                                                            if (isset($timeslot['day'])) {
                                                                $day = convertDay($timeslot['day']);
                                                                $data[] = $day;
                                                            }
                                                        }
                                                        $arr_unique = array_unique($data);
                                                        $check = count($data) !== count($arr_unique);
                                                        if ($check == true) {
                                                            $fail('Duplicate day found! Please re-check the timeslot day before re-submit');
                                                        }
                                                    };
                                                },
                                            ])
                                            ->searchable()
                                            ->reactive()
                                            ->required(),
                                        TextInput::make('quota')
                                            ->label(__('app.booking_quota_per_hour'))
                                            ->helperText(__('app.maximum_booking_per_hour'))
                                            ->integer()
                                            ->required(),
                                        TimePicker::make('start_at')
                                            ->label(__('app.start_at'))
                                            ->native(false)
                                            ->seconds(false)
                                            ->helperText(__('app.in_24_hour_format'))
                                            ->required(),
                                        TimePicker::make('end_at')
                                            ->label(__('app.end_at'))
                                            ->native(false)
                                            ->seconds(false)
                                            ->helperText(__('app.in_24_hour_format'))
                                            ->required(),
                                        Toggle::make('is_active')
                                            ->label(__('app.is_active'))
                                            ->inline(false)
                                            ->default(true)
                                            ->required(),
                                    ])
                                    ->columns(5)
                                    ->minItems(1)
                                    ->maxItems(7)
                                    ->cloneable()
                                    ->collapsible()
                                    ->addActionLabel(__('app.add_timeslot'))
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        if (! is_array($state) || count($state) < 2) {
                                            return;
                                        }

                                        $lastIndex = array_key_last($state);
                                        $lastItem = $state[$lastIndex] ?? [];

                                        // Check if 'day' is set (even if it's 0, like Sunday)
                                        if (array_key_exists('day', $lastItem)) {
                                            $days = collect($state)
                                                ->pluck('day')
                                                ->filter(fn($v) => $v !== null && $v !== '')
                                                ->all();

                                            $uniqueDays = array_unique($days);

                                            if (count($days) !== count($uniqueDays)) {
                                                // Clear duplicate day
                                                $set("timeslots.{$lastIndex}.day", null);
                                            }
                                        }
                                    }),
                            ])
                            ->columns(1)
                            ->reactive()
                            ->visible(function (callable $get) {
                                $facilityAndAmenityId = $get('facility_and_amenity_id');
                                $isBookable = $get('is_bookable');
                                $hasSubAmenities = $get('has_subamenities');

                                if (! $facilityAndAmenityId) {
                                    return false;
                                }

                                $record = FacilityAndAmenity::find($facilityAndAmenityId);

                                return $record?->type === FacilityAmenityTypeEnum::AMENITY->value && $hasSubAmenities === false && $isBookable === true;
                            }),

                        Fieldset::make(__('app.name_of_sub_amenity(bookable)'))
                            ->columnSpanFull()
                            ->schema([
                                Repeater::make('residenceAmenityOptions')
                                    ->label('')
                                    ->schema([
                                        TextInput::make('name')
                                            ->label(__('app.name_en'))
                                            ->required()
                                            ->maxLength(255)
                                            ->helperText(__('app.helper_for_sub_amenity_name')),
                                        TextInput::make('name_in_thai')
                                            ->label(__('app.name_th'))
                                            ->required()
                                            ->maxLength(255)
                                            ->helperText(__('app.helper_for_sub_amenity_name')),
                                        Toggle::make('is_active')
                                            ->inline(false)
                                            ->label(__('app.is_active'))
                                            ->default(true)
                                            ->required(),
                                        Fieldset::make(__('app.booking_and_pricing'))
                                            ->columnSpanFull()
                                            ->schema([
                                                TextInput::make('price_per_hour')
                                                    ->label(__('app.price_per_hour'))
                                                    ->stripCharacters(',')
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->prefix('฿')
                                                    ->required(),
                                                TextInput::make('price_per_day')
                                                    ->label(__('app.price_per_day'))
                                                    ->prefix('฿')
                                                    ->stripCharacters(',')
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->required(),
                                            ])
                                            ->columns(2),
                                        Fieldset::make(__('app.timeslots'))
                                            ->columnSpanFull()
                                            ->schema([
                                                Repeater::make('timeslots')
                                                    ->label('')
                                                    ->schema([
                                                        Select::make('day')
                                                            ->label(__('app.day'))
                                                            ->options(function (callable $get) {
                                                                $allDays = DayOfWeekEnum::options();
                                                                $timeslots = $get('../../timeslots') ?? [];
                                                                $currentIndex = $get('__index');

                                                                $selectedDays = collect($timeslots)
                                                                    ->pluck('day')
                                                                    ->filter(fn ($day) => $day !== null && $day !== '')
                                                                    ->map(fn ($d) => (string) $d)
                                                                    ->all();

                                                                $currentDay = isset($timeslots[$currentIndex]['day']) ? (string) $timeslots[$currentIndex]['day'] : null;

                                                                return collect($allDays)->reject(function ($label, $value) use ($selectedDays, $currentDay) {
                                                                    return in_array((string) $value, $selectedDays) && (string) $value !== $currentDay;
                                                                })->toArray();
                                                            })
                                                            ->getOptionLabelUsing(fn ($value) => DayOfWeekEnum::options()[(string) $value] ?? '-')
                                                            ->rules([
                                                                function (Component $livewire) {
                                                                    return function (string $attribute, $value, Closure $fail) use ($livewire) {
                                                                        $options = $livewire->data['residenceAmenityOptions'] ?? [];

                                                                        foreach ($options as $index => $option) {
                                                                            $timeslots = $option['timeslots'] ?? [];
                                                                            $days = [];

                                                                            foreach ($timeslots as $timeslot) {
                                                                                if (isset($timeslot['day'])) {
                                                                                    $day = convertDay($timeslot['day']);
                                                                                    $days[] = $day;
                                                                                }
                                                                            }

                                                                            if (count($days) !== count(array_unique($days))) {
                                                                                $fail('Duplicate day found! Please re-check the timeslot day before re-submit');
                                                                                break;
                                                                            }
                                                                        }
                                                                    };
                                                                },
                                                            ])
                                                            ->searchable()
                                                            ->reactive()
                                                            ->required(),
                                                        TextInput::make('quota')
                                                            ->label(__('app.booking_quota_per_hour'))
                                                            ->helperText(__('app.maximum_booking_per_hour'))
                                                            ->integer()
                                                            ->required(),
                                                        TimePicker::make('start_at')
                                                            ->label(__('app.start_at'))
                                                            ->native(false)
                                                            ->seconds(false)
                                                            ->helperText(__('app.in_24_hour_format'))
                                                            ->required(),
                                                        TimePicker::make('end_at')
                                                            ->label(__('app.end_at'))
                                                            ->native(false)
                                                            ->seconds(false)
                                                            ->helperText(__('app.in_24_hour_format'))
                                                            ->required(),
                                                        Toggle::make('is_active')
                                                            ->label(__('app.is_active'))
                                                            ->inline(false)
                                                            ->default(true)
                                                            ->required(),
                                                    ])
                                                    ->columns(5)
                                                    ->minItems(1)
                                                    ->maxItems(7)
                                                    ->cloneable()
                                                    ->collapsible()
                                                    ->addActionLabel(__('app.add_timeslot'))
                                                    ->afterStateUpdated(function (callable $set, $state) {
                                                        if (! is_array($state) || count($state) < 2) {
                                                            return;
                                                        }

                                                        $lastIndex = array_key_last($state);
                                                        $lastItem = $state[$lastIndex] ?? [];

                                                        // Check if 'day' is set (even if it's 0, like Sunday)
                                                        if (array_key_exists('day', $lastItem)) {
                                                            $days = collect($state)
                                                                ->pluck('day')
                                                                ->filter(fn ($v) => $v !== null && $v !== '')
                                                                ->all();

                                                            $uniqueDays = array_unique($days);

                                                            if (count($days) !== count($uniqueDays)) {
                                                                // Clear duplicate day
                                                                $set("timeslots.{$lastIndex}.day", null);
                                                            }
                                                        }
                                                    }),
                                            ])
                                            ->columns(1),
                                    ])
                                    ->columns(3)
                                    ->addActionLabel(__('app.add_sub_item')),
                            ])
                            ->columns(1)
                            ->reactive()
                            ->visible(function (callable $get) {
                                $facilityAndAmenityId = $get('facility_and_amenity_id');
                                $hasSubAmenities = $get('has_subamenities');

                                if (! $facilityAndAmenityId) {
                                    return false;
                                }

                                $record = FacilityAndAmenity::find($facilityAndAmenityId);

                                return $record?->type === FacilityAmenityTypeEnum::AMENITY->value && $hasSubAmenities === true;
                            }),
                    ])
            ]);
    }
}
