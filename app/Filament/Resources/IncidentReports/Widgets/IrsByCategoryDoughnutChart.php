<?php

namespace App\Filament\Resources\IncidentReports\Widgets;

use App\Enums\User\RoleType;
use App\Models\IncidentReport;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

class IrsByCategoryDoughnutChart extends ChartWidget
{
    protected ?string $heading = 'Incident Reports by Category';

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

        $query = IncidentReport::with(['reportCategoryItem.reportCategory']);

        // Filter by residence if user is a Security Guard Operation Center
        if ($user->hasRole(RoleType::PROPERTY_MANAGEMENT->value)) {
            $query->whereHas('residence', function (Builder $query) use ($user) {
                $query->where('property_management_user_id', $user->id);
            });
        }

        // Get incident reports grouped by category
        $rawData = $query->get()
            ->groupBy(function ($report) {
                if (is_null($report->report_category_item_id)) {
                    return 'Others';
                }
        
                return $report->reportCategoryItem && $report->reportCategoryItem->reportCategory
                    ? $report->reportCategoryItem->reportCategory->name
                    : 'Others';
            })
            ->map(fn ($reports) => $reports->count())
            ->toArray();

        // Prepare result for chart
        $labels = array_keys($rawData);
        $data = array_values($rawData);

        $backgroundColors = [
            '#1F77B4', // blue
            '#FF7F0E', // orange
            '#2CA02C', // green
            '#D62728', // red
            '#9467BD', // purple
            '#8C564B', // brown
            '#E377C2', // pink
            '#7F7F7F', // gray
            '#BCBD22', // olive
            '#17BECF', // cyan
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Incident Reports by Category',
                    'data' => $data,
                    'backgroundColor' => array_slice($backgroundColors, 0, count($data)),
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}