<?php

namespace App\Filament\Resources\LogisticPartners\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\LogisticPartners\LogisticPartnerResource;
use Filament\Resources\Pages\ListRecords;

class ListLogisticPartners extends ListRecords
{
    protected static string $resource = LogisticPartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
