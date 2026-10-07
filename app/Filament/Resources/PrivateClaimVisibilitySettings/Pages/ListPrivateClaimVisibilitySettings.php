<?php

namespace App\Filament\Resources\PrivateClaimVisibilitySettings\Pages;

use App\Filament\Resources\PrivateClaimVisibilitySettings\PrivateClaimVisibilitySettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPrivateClaimVisibilitySettings extends ListRecords
{
    protected static string $resource = PrivateClaimVisibilitySettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make()
            //     ->visible(fn () => auth()->user()?->hasRole('Super Admin')),
        ];
    }
}
