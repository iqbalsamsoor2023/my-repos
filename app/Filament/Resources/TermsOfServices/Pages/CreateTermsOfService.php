<?php

namespace App\Filament\Resources\TermsOfServices\Pages;

use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use App\Filament\Resources\TermsOfServices\TermsOfServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTermsOfService extends CreateRecord
{
    use Translatable;

    protected static string $resource = TermsOfServiceResource::class;

    protected function getActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }
}
