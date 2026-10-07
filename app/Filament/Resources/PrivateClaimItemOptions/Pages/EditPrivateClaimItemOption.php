<?php

namespace App\Filament\Resources\PrivateClaimItemOptions\Pages;

use App\Filament\Resources\PrivateClaimItemOptions\PrivateClaimItemOptionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPrivateClaimItemOption extends EditRecord
{
    protected static string $resource = PrivateClaimItemOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
