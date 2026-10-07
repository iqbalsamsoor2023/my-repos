<?php

namespace App\Filament\Resources\Bills\Pages;

use App\Enums\Bill\BillStatus;
use App\Filament\Resources\Bills\BillResource;
use App\Models\User;
use Filament\Resources\Pages\ViewRecord;

class ViewBill extends ViewRecord
{
    protected static string $resource = BillResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->status != BillStatus::UNPAID->value && $this->record->status != BillStatus::CANCEL->value) {
            $name = isset($this->record->transactions[0]) ? $this->record->transactions[0]->payer_name : null;

            if (is_null($name) == false) {
                $user = User::where('name', 'like', '%'.$this->record->transactions[0]->payer_name.'%')->first();

                if ($user) {
                    $data['payer_name'] = $user->id;
                } else {
                    $data['payer_name'] = 'Other';
                    $data['payer_name_other'] = isset($this->record->transactions[0]) ? $this->record->transactions[0]->payer_name : null;
                }
            } else {
                $data['payer_name'] = 'Other';
                $data['payer_name_other'] = isset($this->record->transactions[0]) ? $this->record->transactions[0]->payer_name : '-';
            }
        }

        $data['residence_id'] = $this->record->unit->residence_id;
        $data['unit_number'] = $this->record->unit->unit_number;

        return $data;
    }
}
