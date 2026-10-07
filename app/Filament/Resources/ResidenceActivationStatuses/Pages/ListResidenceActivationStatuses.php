<?php

namespace App\Filament\Resources\ResidenceActivationStatuses\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ResidenceActivationStatuses\ResidenceActivationStatusResource;
use Filament\Resources\Pages\ListRecords;

class ListResidenceActivationStatuses extends ListRecords
{
    protected static string $resource = ResidenceActivationStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('menu.new_residence_activation_status')),
        ];
    }
}
