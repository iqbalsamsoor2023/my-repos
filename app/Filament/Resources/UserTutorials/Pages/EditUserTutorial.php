<?php

namespace App\Filament\Resources\UserTutorials\Pages;

use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\UserTutorials\UserTutorialResource;
use Filament\Resources\Pages\EditRecord;

class EditUserTutorial extends EditRecord
{
    use Translatable;

    protected static string $resource = UserTutorialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return __('menu.edit_user_tutorial');
    }
}
