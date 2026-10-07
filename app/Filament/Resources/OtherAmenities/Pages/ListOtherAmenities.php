<?php

namespace App\Filament\Resources\OtherAmenities\Pages;

use Filament\Actions\CreateAction;
use Filament\Tables\Filters\TernaryFilter;
use App\Filament\Resources\OtherAmenities\OtherAmenityResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;

class ListOtherAmenities extends ListRecords
{
    protected static string $resource = OtherAmenityResource::class;

    protected static ?string $title = 'Warranty';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('New Warranty')),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            TernaryFilter::make('is_other_amenity'),
        ];
    }
}
