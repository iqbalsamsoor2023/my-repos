<?php

namespace App\Filament\Resources\ResidenceAmenities\Pages;

use App\Enums\FacilityAndAmenity\FacilityAmenityTypeEnum;
use App\Filament\Resources\ResidenceAmenities\ResidenceAmenityResource;
use App\Models\FacilityAndAmenity;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateResidenceAmenity extends CreateRecord
{
    protected static string $resource = ResidenceAmenityResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $filteredData = collect($data)->only([
            'residence_id',
            'facility_and_amenity_id',
            'is_active',
            'is_bookable',
            'is_claimable',
        ])->toArray();

        return static::getModel()::updateOrCreate(
            [
                'residence_id' => $filteredData['residence_id'],
                'facility_and_amenity_id' => $filteredData['facility_and_amenity_id'],
            ],
            $filteredData
        );
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getState();

        // Get the facility and amenity ID from the form
        $facilityAndAmenityId = $data['facility_and_amenity_id'] ?? null;
        $record = FacilityAndAmenity::find($facilityAndAmenityId);

        // Check if the facility type is AMENITY or FACILITY
        if ($record && $record->type !== FacilityAmenityTypeEnum::FACILITY->value) {
            // Create or update amenity rate only if it's not a FACILITY
            $this->record->amenityRate()->create([
                'price_per_hour' => $data['price_per_hour'] ?? 0,
                'price_per_day' => $data['price_per_day'] ?? 0,
            ]);
        }

        // Initialize an array to hold parent timeslot IDs
        $parentTimeslotIds = [];

        // Process timeslots if they exist in the form data
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

        // Check if 'residenceAmenityOptions' is set, and then process it
        if (isset($data['residenceAmenityOptions']) && is_array($data['residenceAmenityOptions'])) {
            foreach ($data['residenceAmenityOptions'] as $optionData) {
                $option = $this->record->residenceAmenityOptions()->updateOrCreate(
                    ['id' => $optionData['id'] ?? null],
                    [
                        'name' => $optionData['name'],
                        'name_in_thai' => $optionData['name_in_thai'],
                        'is_active' => $optionData['is_active'],
                    ]
                );

                // Create or update amenity rate for the option if it's not a FACILITY type
                if ($record && $record->type !== FacilityAmenityTypeEnum::FACILITY->value) {
                    $option->amenityRate()->updateOrCreate([], [
                        'price_per_hour' => $optionData['price_per_hour'],
                        'price_per_day' => $optionData['price_per_day'],
                    ]);
                }

                // Initialize array for processing timeslot IDs for this option
                $processedTimeslotIds = [];

                // Process timeslots for the option if any exist
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
            }
        }
    }
}
