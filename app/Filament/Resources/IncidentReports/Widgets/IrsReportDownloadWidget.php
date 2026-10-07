<?php

namespace App\Filament\Resources\IncidentReports\Widgets;

use App\Repositories\JobStatusRepository;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Route;

class IrsReportDownloadWidget extends Widget
{
    protected static ?string $heading = 'Incident Reports Download';

    protected string $view = 'filament.resources.incident-reports.widgets.irs-report-download-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '5s';

    public static function canView(): bool
    {
        if (Route::currentRouteName() === 'filament.pages.dashboard') {
            return false;
        }

        return true;
    }

    public function getData()
    {
        $user = auth()->user();

        $repository = new JobStatusRepository;
        $response = $repository->index([
            'project_name' => 'MMB',
            'user_id' => $user->id,
            'type' => [
                'App\Jobs\MYSGOC2\PDF\ProcessIrsExport',
                'App\Jobs\MYSGOC2\XLSX\ProcessIncidentReportExport',
            ],
        ]);

        return $response;
    }

    protected function getViewData(): array
    {
        return [
            'responseData' => $this->getData(),
        ];
    }
}
