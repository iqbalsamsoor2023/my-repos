<?php

namespace App\Filament\Resources\VisitorRemarks\Pages;

use App\Filament\Resources\VisitorRemarks\VisitorRemarkResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVisitorRemark extends EditRecord
{
    protected static string $resource = VisitorRemarkResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_visitor_remark');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
