<?php

namespace App\Filament\Resources\BlacklistVisitors\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\BlacklistVisitors\BlacklistVisitorResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBlacklistVisitors extends ListRecords
{
    protected static string $resource = BlacklistVisitorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
