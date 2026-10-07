<?php

namespace App\Filament\Resources\CheckPointLogs\Widgets;

use App\Enums\AutoSendReport\ModuleType;
use App\Filament\Widgets\ReportDownloadWidget;

class CheckpointLogBulkReportDownloadWidget extends ReportDownloadWidget
{
    public function heading(): ?string
    {
        return __('checkpoint.bulk_downloads');
    }

    public function description(): ?string
    {
        return __('checkpoint.bulk_downloads_description');
    }

    protected function projectName(): string
    {
        return 'MMB2';
    }

    protected function jobTypes(): array
    {
        return ['App\Jobs\ProcessExportDailyReport'];
    }

    protected function additionalFilters(): array
    {
        return ['module' => ModuleType::PGS];
    }

    protected function allowedRoles(): ?array
    {
        return ['Super Admin', 'Admin', 'Property Management'];
    }
}
