<?php

namespace App\Filament\Resources\WarrantySettings\Pages;

use App\Filament\Resources\WarrantySettings\WarrantySettingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWarrantySetting extends EditRecord
{
    protected static string $resource = WarrantySettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
