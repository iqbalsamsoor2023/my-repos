<?php

namespace App\Filament\Resources\PrivateClaimItemOptions\Pages;

use App\Filament\Resources\PrivateClaimItemOptions\PrivateClaimItemOptionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPrivateClaimItemOptions extends ListRecords
{
    protected static string $resource = PrivateClaimItemOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
