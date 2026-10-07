<?php

namespace App\Filament\Resources\PrivateClaimItemSettings\Pages;

use App\Filament\Resources\PrivateClaimItemSettings\PrivateClaimItemSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPrivateClaimItemSettings extends ListRecords
{
    protected static string $resource = PrivateClaimItemSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
