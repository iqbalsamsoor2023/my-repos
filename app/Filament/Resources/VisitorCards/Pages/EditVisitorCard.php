<?php

namespace App\Filament\Resources\VisitorCards\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\VisitorCards\VisitorCardResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVisitorCard extends EditRecord
{
    protected static string $resource = VisitorCardResource::class;

    public function getTitle(): string
    {
        return __('Edit Visitor Card');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
