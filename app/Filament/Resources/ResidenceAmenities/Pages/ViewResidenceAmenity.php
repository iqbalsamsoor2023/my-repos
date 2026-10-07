<?php

namespace App\Filament\Resources\ResidenceAmenities\Pages;

use App\Enums\FacilityAndAmenity\DayOfWeekEnum;
use App\Filament\Resources\ResidenceAmenities\ResidenceAmenityResource;
use Filament\Resources\Pages\ViewRecord;

class ViewResidenceAmenity extends ViewRecord
{
    protected static string $resource = ResidenceAmenityResource::class;

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
}
