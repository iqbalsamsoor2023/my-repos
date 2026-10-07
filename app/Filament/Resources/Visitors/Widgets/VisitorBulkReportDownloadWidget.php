<?php

namespace App\Filament\Resources\Visitors\Widgets;

use App\Enums\AutoSendReport\ModuleType;
use App\Enums\User\RoleType;
use App\Repositories\JobStatusRepository;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class VisitorBulkReportDownloadWidget extends Widget
{
    protected static ?string $heading = 'VMS Bulk Reports Download';

    protected string $view = 'filament.resources.visitors.widgets.visitor-bulk-reports-download-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        return true;
    }

    public function mount(): void
    {
        abort_unless(Auth::user()->hasRole([
            RoleType::PROPERTY_MANAGEMENT->value,
        ]), 403);
    }

    public function getData()
    {
        $user = Auth::user();

        $repository = new JobStatusRepository;
        $response = $repository->index([
            'project_name' => 'MMB2',
            'module' => ModuleType::VMS,
            'user_id' => $user->id,
            'type' => [
                'App\Jobs\ProcessExportDailyReport',
            ],
        ]);

        return $response;
    }
}
