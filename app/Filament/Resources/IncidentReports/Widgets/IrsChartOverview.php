<?php

namespace App\Filament\Resources\IncidentReports\Widgets;

use App\Models\IncidentReport;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class IrsChartOverview extends ChartWidget
{
    protected ?string $heading = 'Incident Reports';

    public ?string $filter = '1 week';

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'half';

    public static function canView(): bool
    {
        if (Route::currentRouteName() === 'filament.pages.dashboard') {
            return false;
        }

        return auth()->user()->hasRole('Property Management');
    }

    protected function getFilters(): ?array
    {
        return [
            '1 week' => '1 Week',
            '2 weeks' => 'Last 2 Weeks',
            '1 month' => 'Last 1 Month',
        ];
    }

    protected function getData(): array
    {
        $user = auth()->user();
        $oneMonthAgo = Carbon::now()->subMonths(1)->toDateString();
        $incidentReports = IncidentReport::where('created_at', '>=', $oneMonthAgo);

        if ($user->hasRole('Super Admin')) {
            $incidentReports = $incidentReports;
        } elseif ($user->hasRole('Property Management')) {
            $incidentReports->where('mmb_residence_id', $user->propertyManagement->id);
        } elseif ($user->hasRole('Property Management Operation Center')) {
            $residenceIds = get_residence_by_property_management_operation_center($user->id);

            $incidentReports->whereIn('mmb_residence_id', $residenceIds);
        }

        $reports = $incidentReports->clone()
            ->select('title as incident_title', DB::raw('count(title) as total_irs'))
            ->groupBy('title')
            ->orderBy('total_irs', 'desc')
            ->limit(3)
            ->get();

        $backgroundColor = [
            'rgb(255, 109, 109, 0.2)',
            'rgb(255, 195, 80, 0.2)',
            'rgb(255, 242, 204, 0.2)',
        ];

        $borderColor = [
            'rgb(255, 109, 109)',
            'rgb(255, 195, 80)',
            'rgb(255, 242, 204)',
        ];

        foreach ($reports as $key => $incidentReport) {
            $dataQuery = Trend::query($incidentReports->clone()->where('title', $incidentReport->incident_title))
                ->between(
                    start: Carbon::now()->sub($this->filter),
                    end: now(),
                );

            switch ($this->filter) {
                case '1 week':
                case '2 weeks':
                case '1 month':
                    $data = $dataQuery->perDay()->count();
                    break;
            }

            $datasets[] = [
                'label' => $incidentReport->incident_title,
                'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                'fill' => true,
                'backgroundColor' => $backgroundColor[$key],
                'borderColor' => $borderColor[$key],
                'tension' => 0.1,
            ];
        }

        // Total
        $dataQuery = Trend::query($incidentReports->clone())
            ->between(
                start: Carbon::now()->sub($this->filter),
                end: now(),
            );

        switch ($this->filter) {
            case '1 week':
            case '2 weeks':
            case '1 month':
                $data = $dataQuery->perDay()->count();
                break;
        }

        $datasets[] = [
            'label' => 'Total',
            'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
            'fill' => true,
            'backgroundColor' => 'rgb(100, 100, 100, 0.2)',
            'borderColor' => 'rgb(100, 100, 100)',
            'tension' => 0.1,
        ];

        return [
            'datasets' => $datasets,
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
