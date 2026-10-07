<?php

namespace App\Filament\Resources\AutoSendReports\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\AutoSendReports\AutoSendReportResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAutoSendReport extends ViewRecord
{
    protected static string $resource = AutoSendReportResource::class;

    public function getTitle(): string
    {
        return __('View Auto Send Report');
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
