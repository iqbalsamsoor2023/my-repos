<?php

namespace App\Filament\Resources\Vms\Pages;

use App\Filament\Resources\Vms\VmsResource;
use Filament\Resources\Pages\ViewRecord;

class ViewVms extends ViewRecord
{
    protected static string $resource = VmsResource::class;

    protected static ?string $title = 'View Visitor Management';

    protected function canDelete(): bool
    {
        return false;
    }
}
