<?php

namespace App\Filament\Resources\Banks\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Banks\BankResource;
use Filament\Resources\Pages\ListRecords;

class ListBanks extends ListRecords
{
    protected static string $resource = BankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('menu.settingManagement.create_bank')),
        ];
    }
}
