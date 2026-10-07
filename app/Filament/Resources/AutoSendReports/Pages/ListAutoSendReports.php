<?php

namespace App\Filament\Resources\AutoSendReports\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\AutoSendReports\AutoSendReportResource;
use Filament\Resources\Pages\ListRecords;

class ListAutoSendReports extends ListRecords
{
    protected static string $resource = AutoSendReportResource::class;

    public function getTitle(): string
    {
        return __('menu.auto_send_reports');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('menu.new_auto_send_report')),
        ];
    }
}
