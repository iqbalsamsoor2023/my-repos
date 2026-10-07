<?php

namespace App\Filament\Resources\PrivateClaimItems\Pages;

use App\Filament\Resources\PrivateClaimItems\PrivateClaimItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPrivateClaimItem extends EditRecord
{
    protected static string $resource = PrivateClaimItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
