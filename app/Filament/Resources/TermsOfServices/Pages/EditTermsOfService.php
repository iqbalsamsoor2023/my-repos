<?php

namespace App\Filament\Resources\TermsOfServices\Pages;

use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use App\Filament\Resources\TermsOfServices\TermsOfServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTermsOfService extends EditRecord
{
    use Translatable;

    protected static string $resource = TermsOfServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }
}
