<?php

namespace App\Filament\Resources\Residences\Schemas;

use App\Enums\FacilityAndAmenity\AmenityTypeEnum;
use App\Enums\FacilityAndAmenity\FacilityAmenityTypeEnum;
use App\Models\FacilityAndAmenity;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

class ResidenceAmenitiesForm
{
    public static function getForm(): array
    {
        $nameField = App::getLocale() === 'th' ? 'name_in_thai' : 'name';

        $items = FacilityAndAmenity::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query
                    ->where('type', FacilityAmenityTypeEnum::AMENITY->value)
                    ->orWhere(function ($facilityQuery): void {
                        $facilityQuery
                            ->where('type', FacilityAmenityTypeEnum::FACILITY->value)
                            ->where('name', '!=', 'Others');
                    });
            })
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'name_in_thai', 'type', 'amenity_type']);

        $toToggles = static fn (Collection $collection): array => $collection
            ->map(fn (FacilityAndAmenity $amenity) => Toggle::make('amenity_'.$amenity->id)
                ->label((string) ($amenity->{$nameField} ?: $amenity->name)))
            ->toArray();

        $indoorAmenities = $items
            ->where('type', FacilityAmenityTypeEnum::AMENITY->value)
            ->where('amenity_type', AmenityTypeEnum::INDOOR->value)
            ->values();

        $outdoorAmenities = $items
            ->where('type', FacilityAmenityTypeEnum::AMENITY->value)
            ->where('amenity_type', AmenityTypeEnum::OUTDOOR->value)
            ->values();

        $facilities = $items
            ->where('type', FacilityAmenityTypeEnum::FACILITY->value)
            ->values();

        return [
            Grid::make(2)
                ->columnSpanFull()
                ->schema([
                    Fieldset::make(__('app.amenities'))
                        ->columnSpanFull()
                        ->schema([
                            Fieldset::make(__('app.indoor'))
                                ->columnSpanFull()
                                ->schema([
                                    ...$toToggles($indoorAmenities),
                                ]),
                            Fieldset::make(__('app.outdoor'))
                                ->columnSpanFull()
                                ->schema([
                                    ...$toToggles($outdoorAmenities),
                                ]),
                        ]),
                    Fieldset::make(__('app.facilities'))
                        ->columnSpanFull()
                        ->schema([
                            ...$toToggles($facilities),
                        ]),
                ]),
        ];
    }
}
