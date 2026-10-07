<?php

namespace App\Filament\Resources\Parcels\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Parcels\ParcelResource;
use App\Filament\Resources\Parcels\Widgets\CourierBrandsChart;
use App\Filament\Resources\Parcels\Widgets\CourierBrandsStatsOverview;
use App\Filament\Resources\Parcels\Widgets\ParcelCreationsChart;
use App\Filament\Resources\Parcels\Widgets\ParcelStatusStatsOverview;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListParcels extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = ParcelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('menu.new_parcel')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ParcelCreationsChart::class,
            ParcelStatusStatsOverview::class,
            CourierBrandsChart::class,
            CourierBrandsStatsOverview::class,
        ];
    }
}
