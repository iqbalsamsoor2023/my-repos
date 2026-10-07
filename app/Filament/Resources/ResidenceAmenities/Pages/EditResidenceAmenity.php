<?php

namespace App\Filament\Resources\ResidenceAmenities\Pages;

use Filament\Actions\DeleteAction;
use App\Enums\FacilityAndAmenity\DayOfWeekEnum;
use App\Enums\FacilityAndAmenity\FacilityAmenityTypeEnum;
use App\Filament\Resources\ResidenceAmenities\ResidenceAmenityResource;
use App\Models\FacilityAndAmenity;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditResidenceAmenity extends EditRecord
{
    protected static string $resource = ResidenceAmenityResource::class;

    protected array $savedFormData = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $hasSubAmenities = $this->record->residenceAmenityOptions->isNotEmpty();
        $data['has_subamenities'] = $hasSubAmenities;

        $data['residenceAmenityOptions'] = $this->record->residenceAmenityOptions
            ->map(function ($option) {
                return [
                    'id' => $option->id,
                    'name' => $option->name,
                    'name_in_thai' => $option->name_in_thai,
                    'is_active' => $option->is_active,
                    'booking_per_hour' => $option->amenityRate?->booking_per_hour,
                    'price_per_hour' => $option->amenityRate?->price_per_hour,
                    'price_per_day' => $option->amenityRate?->price_per_day,
                    'timeslots' => $option->amenityTimeslots?->map(function ($slot) {
                        return [
                            'id' => $slot->id,
                            'day' => DayOfWeekEnum::tryFrom($slot->day)?->value ?? null,
                            'quota' => $slot->quota,
                            'start_at' => $slot->start_at,
                            'end_at' => $slot->end_at,
                            'is_active' => $slot->is_active,
                        ];
                    })->toArray() ?? [],
                ];
            })->toArray();

        $data['timeslots'] = $this->record->residenceAmenityOptions->isEmpty() ? $this->record->amenityTimeslots->map(function ($slot) {
            return [
                'id' => $slot->id,
                'day' => DayOfWeekEnum::tryFrom($slot->day)?->value ?? null,
                'quota' => $slot->quota,
                'start_at' => $slot->start_at,
                'end_at' => $slot->end_at,
                'is_active' => $slot->is_active,
            ];
        })->toArray() : [];

        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->form->getState();
        $hasSubOptions = ! empty($data['residenceAmenityOptions']); // Check if sub-amenities exist

        // Get the facility and amenity type from the form
        $facilityAndAmenityId = $data['facility_and_amenity_id'] ?? null;
        $record = FacilityAndAmenity::find($facilityAndAmenityId);

        // Only save parent amenity rate if no sub-options exist and if it's not a facility
        if (! $hasSubOptions && $record && $record->type !== FacilityAmenityTypeEnum::FACILITY->value) {
            // Update or create the parent amenity rate only if it's not a FACILITY
            $this->record->amenityRate()->updateOrCreate([], [
                'price_per_hour' => $data['price_per_hour'] ?? null,
                'price_per_day' => $data['price_per_day'] ?? null,
            ]);

            $parentTimeslotIds = [];
            foreach ($data['timeslots'] ?? [] as $slotData) {
                $day = convertDay($slotData['day']);

                $timeslot = $this->record->amenityTimeslots()->updateOrCreate(
                    ['id' => $slotData['id'] ?? null],
                    [
                        'day' => $day,
                        'quota' => $slotData['quota'],
                        'start_at' => $slotData['start_at'],
                        'end_at' => $slotData['end_at'],
                        'is_active' => $slotData['is_active'] ?? true,
                    ]
                );

                if ($timeslot->id) {
                    $parentTimeslotIds[] = $timeslot->id;
                }
            }

            // Remove any old timeslots not present in form
            $this->record->amenityTimeslots()
                ->whereNotIn('id', $parentTimeslotIds)
                ->delete();
        } else {
            // When sub-amenities exist or it's a facility, delete the parent amenity rate and timeslots
            if ($record && $record->type !== FacilityAmenityTypeEnum::FACILITY->value) {
                $this->record?->amenityRate()?->delete();
                $this->record?->amenityTimeslots()?->delete();
            }
        }

        // Check if 'residenceAmenityOptions' key exists before using it
        $existingOptionIds = isset($data['residenceAmenityOptions'])
        ? collect($data['residenceAmenityOptions'])->pluck('id')->filter()->toArray()
        : [];

        // If sub-amenities exist, delete any sub-amenities that no longer exist in the form
        $this->record->residenceAmenityOptions()
            ->whereNotIn('id', $existingOptionIds)
            ->each(function ($option) {
                $option->amenityRate()?->delete();
                $option->amenityTimeslots()?->delete(); // Delete timeslots associated with this option
                $option->delete(); // Delete the sub-amenity itself
            });

        // Handle create/update for sub-amenities
        foreach ($data['residenceAmenityOptions'] ?? [] as $optionData) {
            $option = $this->record->residenceAmenityOptions()->updateOrCreate(
                ['id' => $optionData['id'] ?? null],
                [
                    'name' => $optionData['name'],
                    'name_in_thai' => $optionData['name_in_thai'],
                    'is_active' => $optionData['is_active'],
                ]
            );

            // Update or create amenity rate for this sub-amenity only if it's not a FACILITY
            if ($record && $record->type !== FacilityAmenityTypeEnum::FACILITY->value) {
                $option->amenityRate()->updateOrCreate([], [
                    'price_per_hour' => $optionData['price_per_hour'] ?? null,
                    'price_per_day' => $optionData['price_per_day'] ?? null,
                ]);
            }

            // Track timeslot IDs to ensure cleanup of old timeslots
            $processedTimeslotIds = [];

            // Handle timeslot creation or updating for this sub-amenity
            foreach ($optionData['timeslots'] ?? [] as $timeslotData) {
                $day = convertDay($timeslotData['day']);
                $timeslot = $option->amenityTimeslots()->updateOrCreate(
                    ['id' => $timeslotData['id'] ?? null],
                    [
                        'day' => $day,
                        'quota' => $timeslotData['quota'],
                        'start_at' => $timeslotData['start_at'],
                        'end_at' => $timeslotData['end_at'],
                        'is_active' => $timeslotData['is_active'] ?? true,
                    ]
                );

                if ($timeslot->id) {
                    $processedTimeslotIds[] = $timeslot->id;
                }
            }

            // Delete removed timeslots that were not in the form data for this option
            $option->amenityTimeslots()
                ->whereNotIn('id', $processedTimeslotIds)
                ->delete();
        }
    }
}
