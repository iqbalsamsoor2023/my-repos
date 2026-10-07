<?php

namespace App\Filament\Resources\AutoSendReports\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use App\Filament\Resources\AutoSendReports\AutoSendReportResource;
use Filament\Resources\Pages\EditRecord;

class EditAutoSendReport extends EditRecord
{
    protected static string $resource = AutoSendReportResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_auto_send_report');
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
