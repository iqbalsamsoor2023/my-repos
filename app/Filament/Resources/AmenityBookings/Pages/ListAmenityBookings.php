<?php

namespace App\Filament\Resources\AmenityBookings\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\AmenityBookings\Widgets\BookingChartOverview;
use App\Filament\Resources\AmenityBookings\AmenityBookingResource;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListAmenityBookings extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = AmenityBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BookingChartOverview::class,
        ];
    }
}
