<?php

namespace App\Filament\Resources\Units\Pages;

use App\Filament\Resources\Units\UnitResource;
use Filament\Resources\Pages\EditRecord;

class EditUnit extends EditRecord
{
    protected static string $resource = UnitResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_unit');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
