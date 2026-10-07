<?php

namespace App\Filament\Resources\Pdpas\Pages;

use Filament\Actions\CreateAction;
use App\Enums\ResourceMaterial\ResourceTypeEnum;
use App\Filament\Resources\Pdpas\PdpaResource;
use App\Models\Erp\StaticContent;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPdpas extends ListRecords
{
    protected static string $resource = PdpaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => ! StaticContent::where('type', ResourceTypeEnum::PRIVACY_POLICY->value)->exists()),
        ];
    }
}
