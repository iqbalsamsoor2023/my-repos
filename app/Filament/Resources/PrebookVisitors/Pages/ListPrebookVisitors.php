<?php

namespace App\Filament\Resources\PrebookVisitors\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\PrebookVisitors\PrebookVisitorResource;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListPrebookVisitors extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = PrebookVisitorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('menu.new_prebook_helpdesk')),
        ];
    }
}
