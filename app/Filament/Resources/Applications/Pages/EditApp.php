<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use Filament\Resources\Pages\EditRecord;

class EditApp extends EditRecord
{
    protected static string $resource = ApplicationResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_app_version');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
