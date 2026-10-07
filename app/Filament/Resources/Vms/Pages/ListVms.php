<?php

namespace App\Filament\Resources\Vms\Pages;

use App\Filament\Resources\Vms\VmsResource;
use Filament\Resources\Pages\ListRecords;

class ListVms extends ListRecords
{
    protected static string $resource = VmsResource::class;

    public function getTitle(): string
    {
        return __('Visitor Managements');
    }
}
