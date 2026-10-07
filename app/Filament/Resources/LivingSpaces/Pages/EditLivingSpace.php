<?php

namespace App\Filament\Resources\LivingSpaces\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\LivingSpaces\LivingSpaceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLivingSpace extends EditRecord
{
    protected static string $resource = LivingSpaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
