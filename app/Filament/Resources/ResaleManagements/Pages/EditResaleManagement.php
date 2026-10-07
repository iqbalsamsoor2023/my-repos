<?php

namespace App\Filament\Resources\ResaleManagements\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\ResaleManagements\ResaleManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditResaleManagement extends EditRecord
{
    protected static string $resource = ResaleManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
