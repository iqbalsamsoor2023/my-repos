<?php

namespace App\Filament\Resources\ResidenceFacilitiesResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ResidenceFacilitiesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListResidenceFacilities extends ListRecords
{
    protected static string $resource = ResidenceFacilitiesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
