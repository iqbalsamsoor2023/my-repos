<?php

namespace App\Filament\Resources\ProductOperationGuides\Pages;

use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\ProductOperationGuides\ProductOperationGuideResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductOperationGuide extends EditRecord
{
    use Translatable;

    protected static string $resource = ProductOperationGuideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            DeleteAction::make(),
        ];
    }
}
