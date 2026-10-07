<?php

namespace App\Filament\Resources\TermsOfServices\Pages;

use Filament\Actions\CreateAction;
use App\Enums\ResourceMaterial\ResourceTypeEnum;
use App\Filament\Resources\TermsOfServices\TermsOfServiceResource;
use App\Models\Erp\StaticContent;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTermsOfServices extends ListRecords
{
    protected static string $resource = TermsOfServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => ! StaticContent::where('type', ResourceTypeEnum::TERMS_OF_SERVICE->value)->exists()),
        ];
    }
}
