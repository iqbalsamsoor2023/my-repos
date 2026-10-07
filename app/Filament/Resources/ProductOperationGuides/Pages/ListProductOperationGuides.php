<?php

namespace App\Filament\Resources\ProductOperationGuides\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ProductOperationGuides\ProductOperationGuideResource;
use Filament\Resources\Pages\ListRecords;

class ListProductOperationGuides extends ListRecords
{
    protected static string $resource = ProductOperationGuideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
