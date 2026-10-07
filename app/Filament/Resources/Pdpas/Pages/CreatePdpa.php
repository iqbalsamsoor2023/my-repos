<?php

namespace App\Filament\Resources\Pdpas\Pages;

use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use App\Filament\Resources\Pdpas\PdpaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePdpa extends CreateRecord
{
    use Translatable;

    protected static string $resource = PdpaResource::class;

    protected function getActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
