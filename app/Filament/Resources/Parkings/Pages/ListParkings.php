<?php

namespace App\Filament\Resources\Parkings\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Parkings\ParkingResource;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListParkings extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = ParkingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('menu.new_parking')),
        ];
    }
}
