<?php

namespace App\Filament\Resources\CheckPointLogs\Pages;

use App\Filament\Resources\CheckPointLogs\CheckPointLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCheckPointLog extends ViewRecord
{
    protected static string $resource = CheckPointLogResource::class;

    public function getTitle(): string
    {
        return __('menu.view_patrol_checkpoint');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['residence'] = $this->record->checkpoint->residence->name;
        $data['name'] = isset($this->record->user) ? $this->record->user->name : '-';
        $data['checkpoint'] = $this->record->checkpoint->name;

        return $data;
    }
}
