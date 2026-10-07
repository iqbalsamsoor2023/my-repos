<?php

namespace App\Filament\Resources\MaintenanceProgressions\Pages;

use App\Filament\Resources\MaintenanceProgressions\MaintenanceProgressionResource;
use Filament\Resources\Pages\EditRecord;

class EditMaintenanceProgression extends EditRecord
{
    protected static string $resource = MaintenanceProgressionResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_maintenance_prgression');
    }
}
