<?php

namespace App\Filament\Resources\PrivateClaimCategories\Pages;

use App\Filament\Resources\PrivateClaimCategories\PrivateClaimCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPrivateClaimCategory extends EditRecord
{
    protected static string $resource = PrivateClaimCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
