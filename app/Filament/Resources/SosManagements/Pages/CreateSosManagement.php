<?php

namespace App\Filament\Resources\SosManagements\Pages;

use App\Filament\Resources\SosManagements\SosManagementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSosManagement extends CreateRecord
{
    protected static string $resource = SosManagementResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
