<?php

namespace App\Filament\Resources\HomeAppliances\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\HomeAppliances\HomeApplianceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHomeAppliance extends EditRecord
{
    protected static string $resource = HomeApplianceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
