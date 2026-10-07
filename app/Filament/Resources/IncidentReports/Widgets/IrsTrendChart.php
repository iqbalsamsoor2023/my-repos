<?php

namespace App\Filament\Resources\IncidentReports\Widgets;

use App\Enums\User\RoleType;
use App\Models\IncidentReport;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

class IrsTrendChart extends ChartWidget
{
    protected ?string $heading = 'IRS Cases Trend';

    public ?string $filter = '1 month';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    protected ?string $maxHeight = '300px';

    public static function canView(): bool
    {
        if (Route::currentRouteName() === 'filament.admin.pages.dashboard') {
            return false;
        }

        return auth()->user()->hasRole(RoleType::PROPERTY_MANAGEMENT->value);
    }

    protected function getFilters(): ?array
    {
        return [
            '1 week' => 'Last 1 Week',
            '2 weeks' => 'Last 2 Weeks',
            '1 month' => 'Last 1 Month',
        ];
    }

    protected function getData(): array
    {
        $user = auth()->user();

        $query = IncidentReport::with('reportCategoryItem');

        // Filter by SGOC residences
        if ($user->hasRole(RoleType::PROPERTY_MANAGEMENT->value)) {
            $query->whereHas('residence', function (Builder $query) use ($user) {
                $query->where('property_management_user_id', $user->id);
            });
        }

        $reports = $query->get();

        // Get all unique categories (including 'Others')
        $categories = $reports->map(fn($ir) => $ir->reportCategoryItem?->title ?? 'Others')
                              ->unique()
                              ->values()
                              ->toArray();

        $datasets = [];
        $backgroundColors = [
            'rgba(59, 130, 246, 0.2)',
            'rgba(16, 185, 129, 0.2)',
            'rgba(239, 68, 68, 0.2)',
            'rgba(202, 160, 46, 0.2)',
            'rgba(134, 46, 201, 0.2)',
            'rgba(99, 102, 241, 0.2)',
            'rgba(156, 163, 175, 0.2)',
        ];
        $borderColors = [
            'rgba(59, 130, 246)',
            'rgba(16, 185, 129)',
            'rgba(239, 68, 68)',
            'rgba(202, 160, 46)',
            'rgba(134, 46, 201)',
            'rgba(99, 102, 241)',
            'rgba(156, 163, 175)',
        ];

        foreach ($categories as $key => $category) {
            $categoryQuery = $query->clone();

            if ($category === 'Others') {
                $categoryQuery->whereNull('report_category_item_id');
            } else {
                $categoryQuery->whereHas('reportCategoryItem', fn($q) => $q->where('title', $category));
            }

            $trendQuery = Trend::query($categoryQuery)
                ->between(
                    start: Carbon::now()->sub($this->filter),
                    end: now()
                );

            $data = match ($this->filter) {
                '1 week', '2 weeks', '1 month' => $trendQuery->perDay()->count(),
            };

            $datasets[] = [
                'label' => $category,
                'data' => $data->map(fn(TrendValue $value) => $value->aggregate),
                'fill' => true,
                'backgroundColor' => $backgroundColors[$key % count($backgroundColors)],
                'borderColor' => $borderColors[$key % count($borderColors)],
                'tension' => 0.1,
            ];
        }

        // Total dataset
        $totalData = Trend::query($query->clone())
            ->between(
                start: Carbon::now()->sub($this->filter),
                end: now()
            )
            ->perDay()->count();

        $datasets[] = [
            'label' => 'Total',
            'data' => $totalData->map(fn(TrendValue $value) => $value->aggregate),
            'fill' => true,
            'backgroundColor' => 'rgba(100, 100, 100, 0.2)',
            'borderColor' => 'rgba(100, 100, 100)',
            'tension' => 0.1,
        ];

        return [
            'datasets' => $datasets,
            'labels' => $totalData->map(fn(TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}