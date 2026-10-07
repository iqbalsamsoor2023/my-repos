<?php

namespace App\Filament\Resources\VisitorPurposes\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\VisitorPurposes\VisitorPurposeResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVisitorPurpose extends EditRecord
{
    protected static string $resource = VisitorPurposeResource::class;

    public function getTitle(): string
    {
        return __('Edit Visitor Purpose');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
