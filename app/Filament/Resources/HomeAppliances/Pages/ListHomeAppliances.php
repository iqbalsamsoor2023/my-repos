<?php

namespace App\Filament\Resources\HomeAppliances\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\HomeAppliances\HomeApplianceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHomeAppliances extends ListRecords
{
    protected static string $resource = HomeApplianceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
