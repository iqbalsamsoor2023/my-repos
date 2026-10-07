<?php

namespace App\Filament\Resources\SosManagements\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\SosManagements\SosManagementResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSosManagement extends EditRecord
{
    protected static string $resource = SosManagementResource::class;

    public function getTitle(): string
    {
        return __('Edit SOS Management');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
