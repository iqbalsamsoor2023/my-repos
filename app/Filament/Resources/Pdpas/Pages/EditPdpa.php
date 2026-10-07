<?php

namespace App\Filament\Resources\Pdpas\Pages;

use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use App\Filament\Resources\Pdpas\PdpaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPdpa extends EditRecord
{
    use Translatable;

    protected static string $resource = PdpaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }
}
