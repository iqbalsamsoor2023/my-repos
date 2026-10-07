<?php

namespace App\Filament\Resources\VisitorParkings\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\VisitorParkings\VisitorParkingResource;
use Filament\Resources\Pages\EditRecord;

class EditVisitorParking extends EditRecord
{
    protected static string $resource = VisitorParkingResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_visitor_parking');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
