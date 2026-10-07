<?php

namespace App\Filament\Resources\UserTutorials\Pages;

use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use App\Filament\Resources\UserTutorials\UserTutorialResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUserTutorial extends CreateRecord
{
    use Translatable;

    protected static string $resource = UserTutorialResource::class;

    public function getTitle(): string
    {
        return __('menu.create_user_tutorial');
    }

    protected function getActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }
}
