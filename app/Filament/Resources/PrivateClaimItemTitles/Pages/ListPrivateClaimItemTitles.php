<?php

namespace App\Filament\Resources\PrivateClaimItemTitles\Pages;

use App\Filament\Resources\PrivateClaimItemTitles\PrivateClaimItemTitleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPrivateClaimItemTitles extends ListRecords
{
    protected static string $resource = PrivateClaimItemTitleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
