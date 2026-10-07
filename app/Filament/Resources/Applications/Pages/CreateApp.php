<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateApp extends CreateRecord
{
    protected static string $resource = ApplicationResource::class;

    public function getTitle(): string
    {
        return __('menu.create_app_version');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
