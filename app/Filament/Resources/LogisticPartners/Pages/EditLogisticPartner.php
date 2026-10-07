<?php

namespace App\Filament\Resources\LogisticPartners\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\LogisticPartners\LogisticPartnerResource;
use Filament\Resources\Pages\EditRecord;

class EditLogisticPartner extends EditRecord
{
    protected static string $resource = LogisticPartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
