<?php

namespace App\Filament\Resources\AutoSendReports\Pages;

use App\Filament\Resources\AutoSendReports\AutoSendReportResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAutoSendReport extends CreateRecord
{
    protected static string $resource = AutoSendReportResource::class;

    public function getTitle(): string
    {
        return __('Create Auto Send Report');
    }
}
