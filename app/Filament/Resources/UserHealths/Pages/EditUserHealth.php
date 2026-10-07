<?php

namespace App\Filament\Resources\UserHealths\Pages;

use App\Filament\Resources\UserHealths\UserHealthResource;
use Filament\Resources\Pages\EditRecord;

class EditUserHealth extends EditRecord
{
    protected static string $resource = UserHealthResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_user_health');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
