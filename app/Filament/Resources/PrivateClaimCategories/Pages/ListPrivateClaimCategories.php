<?php

namespace App\Filament\Resources\PrivateClaimCategories\Pages;

use App\Filament\Resources\PrivateClaimCategories\PrivateClaimCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPrivateClaimCategories extends ListRecords
{
    protected static string $resource = PrivateClaimCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
