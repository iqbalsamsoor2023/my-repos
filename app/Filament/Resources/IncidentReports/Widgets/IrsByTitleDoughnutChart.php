<?php

namespace App\Filament\Resources\IncidentReports\Widgets;

use App\Enums\User\RoleType;
use App\Models\IncidentReport;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

class IrsByTitleDoughnutChart extends ChartWidget
{
    protected ?string $heading = 'Incident Reports by Title';

    protected int | string | array $columnSpan = 1;

    protected ?string $pollingInterval = '60s';

    protected ?string $maxHeight = '300px';

    public static function canView(): bool
    {
        if (Route::currentRouteName() === 'filament.admin.pages.dashboard') {
            return false;
        }

        return auth()->user()->hasRole(RoleType::PROPERTY_MANAGEMENT->value);
    }

    protected function getData(): array
    {
        $user = auth()->user();

        // Load reports with reportCategoryItem relation
        $query = IncidentReport::with('reportCategoryItem');

        // Filter by residence if user is a Security Guard Operation Center
        if ($user->hasRole(RoleType::PROPERTY_MANAGEMENT->value)) {
            $query->whereHas('residence', function (Builder $query) use ($user) {
                $query->where('property_management_user_id', $user->id);
            });
        }

        // Get all reports
        $reports = $query->get();

        // Group by reportCategoryItem title or 'Others' if NULL
        $grouped = $reports->groupBy(function ($report) {
            return $report->reportCategoryItem?->title ?? 'Others';
        });

        // Count per title
        $result = [];
        foreach ($grouped as $title => $items) {
            $result[] = [
                'label' => $title,
                'count' => count($items),
            ];
        }

        // Color palette
        $colors = [
            '#1F77B4', '#FF7F0E', '#2CA02C', '#D62728', '#9467BD', 
            '#8C564B', '#E377C2', '#7F7F7F', '#BCBD22', '#17BECF',
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Incident Reports by Title',
                    'data' => array_column($result, 'count'),
                    'backgroundColor' => array_slice($colors, 0, count($result)),
                ],
            ],

            'labels' => array_column($result, 'label'),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}