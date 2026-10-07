<?php

namespace App\Filament\Resources\UserTutorials\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\UserTutorials\UserTutorialResource;
use Filament\Resources\Pages\ListRecords;

class ListUserTutorials extends ListRecords
{
    protected static string $resource = UserTutorialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('menu.new_user_tutorial')),
        ];
    }
}
