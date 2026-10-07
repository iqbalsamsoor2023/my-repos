<?php

namespace App\Filament\Resources\ResidenceFacilitiesResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\ResidenceFacilitiesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditResidenceFacilities extends EditRecord
{
    protected static string $resource = ResidenceFacilitiesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
