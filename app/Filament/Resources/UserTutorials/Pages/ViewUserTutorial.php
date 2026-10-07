<?php

namespace App\Filament\Resources\UserTutorials\Pages;

use App\Filament\Resources\UserTutorials\UserTutorialResource;
use Filament\Resources\Pages\ViewRecord;
use LaraZeus\SpatieTranslatable\Resources\Pages\ViewRecord\Concerns\Translatable;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;

class ViewUserTutorial extends ViewRecord
{    
    use Translatable;

    protected static string $resource = UserTutorialResource::class;

    protected function getHeaderActions(): array
    {
        return [
           LocaleSwitcher::make(),
        ];
    }

    public function getTitle(): string
    {
        return __('menu.view_user_tutorial');
    }
}
