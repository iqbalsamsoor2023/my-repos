<?php

namespace App\Filament\Resources\Amenities\Pages;

use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\CreateAction;
use App\Filament\Resources\Amenities\AmenityResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListAmenities extends ListRecords
{
    protected static string $resource = AmenityResource::class;

    protected function getTableFilters(): array
    {
        return [
            TernaryFilter::make('is_out_warranty'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
