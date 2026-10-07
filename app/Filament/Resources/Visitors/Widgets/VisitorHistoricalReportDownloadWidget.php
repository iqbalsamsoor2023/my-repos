<?php

namespace App\Filament\Resources\Visitors\Widgets;

use App\Enums\User\RoleType;
use App\Repositories\JobStatusRepository;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class VisitorHistoricalReportDownloadWidget extends Widget
{
    protected static ?string $heading = 'Visitors Download';

    protected string $view = 'filament.resources.visitors.widgets.visitor-report-download-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '5s';

    public function mount()
    {
        abort_unless(Auth::user()->hasRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::ADMIN->value,
            RoleType::PROPERTY_MANAGEMENT->value,
            RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value,
            RoleType::DEVELOPER->value]), 403);
    }

    public function getData()
    {
        $user = Auth::user();

        $repository = new JobStatusRepository;
        $response = $repository->index([
            'project_name' => 'MMB2',
            'data_type' => 'historical',
            'user_id' => $user->id,
            'type' => [
                'App\\Jobs\\MMB2\\XLSX\\ProcessVmsExport',
            ],
        ]);

        return $response;
    }
}
