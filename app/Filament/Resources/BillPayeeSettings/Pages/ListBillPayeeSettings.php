<?php

namespace App\Filament\Resources\BillPayeeSettings\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\BillPayeeSettings\BillPayeeSettingResource;
use Filament\Resources\Pages\ListRecords;
use Livewire\Component;

class ListBillPayeeSettings extends ListRecords
{
    protected static string $resource = BillPayeeSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('menu.new_bill_reminder_setting'))
                ->hidden(function (Component $livewire) {
                    if (auth()->user()->hasRole('Property Management')) {
                        if ($livewire->getTableQuery()->count() > 0) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                }),
        ];
    }
}
