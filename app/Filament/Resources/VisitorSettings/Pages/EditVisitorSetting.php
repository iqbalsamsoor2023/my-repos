<?php

namespace App\Filament\Resources\VisitorSettings\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\VisitorSettings\VisitorSettingResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVisitorSetting extends EditRecord
{
    protected static string $resource = VisitorSettingResource::class;

    public function getTitle(): string
    {
        return __('Edit Visitor Setting');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
