<?php

namespace App\Filament\Resources\UserHealths\Pages;

use App\Filament\Resources\UserHealths\UserHealthResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUserHealth extends CreateRecord
{
    protected static string $resource = UserHealthResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
