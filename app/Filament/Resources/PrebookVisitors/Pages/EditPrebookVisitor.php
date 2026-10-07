<?php

namespace App\Filament\Resources\PrebookVisitors\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\PrebookVisitors\PrebookVisitorResource;
use Filament\Resources\Pages\EditRecord;

class EditPrebookVisitor extends EditRecord
{
    protected static string $resource = PrebookVisitorResource::class;

    public function getTitle(): string
    {
        return __('Edit Prebook Helpdesk');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['residence_id'] = $this->record->unit->residence_id;

        $visitor_purpose = $data['visitor_purpose'];
        $visitor_purpose_list = ['Receive/Delivery', 'Drop Off/Pick Up', 'Contractor/Worker', 'Visitor Parking', 'VIP'];

        if (in_array($visitor_purpose, $visitor_purpose_list) == false) {
            $data['visitor_purpose'] = 'Other';
            $data['visitor_purpose_other'] = $this->record->visitor_purpose;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['visitor_purpose'] == 'Other') {
            $data['visitor_purpose'] = $data['visitor_purpose_other'];
        }

        return $data;
    }
}
