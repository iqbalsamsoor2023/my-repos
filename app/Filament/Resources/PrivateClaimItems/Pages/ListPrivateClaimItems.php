<?php

namespace App\Filament\Resources\PrivateClaimItems\Pages;

use App\Filament\Resources\PrivateClaimItems\PrivateClaimItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPrivateClaimItems extends ListRecords
{
    protected static string $resource = PrivateClaimItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
