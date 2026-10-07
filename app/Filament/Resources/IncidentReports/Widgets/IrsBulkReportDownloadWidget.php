<?php

namespace App\Filament\Resources\IncidentReports\Widgets;

use App\Enums\AutoSendReport\ModuleType;
use App\Repositories\JobStatusRepository;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class IrsBulkReportDownloadWidget extends Widget
{
    protected static ?string $heading = 'IRS Bulk Reports Download';

    protected string $view = 'filament.resources.incident-reports.widgets.irs-bulk-reports-download-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '5s';

    public static function canView(): bool
    {
        return true;
    }

    public function mount(): void
    {
        abort_unless(Auth::user()->hasRole(['Property Management', 'Super Admin']), 403);
    }

    public function getData()
    {
        $user = Auth::user();

        $repository = new JobStatusRepository;
        $response = $repository->index([
            'project_name' => 'MMB2',
            'module' => ModuleType::IRS,
            'user_id' => $user->id,
            'type' => [
                'App\Jobs\ProcessExportDailyReport',
            ],
        ]);

        return $response;
    }
}
