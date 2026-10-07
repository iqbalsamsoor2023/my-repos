<?php

namespace App\Filament\Resources\Calculations\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Calculations\CalculationResource;
use Filament\Resources\Pages\ListRecords;

class ListCalculations extends ListRecords
{
    protected static string $resource = CalculationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
