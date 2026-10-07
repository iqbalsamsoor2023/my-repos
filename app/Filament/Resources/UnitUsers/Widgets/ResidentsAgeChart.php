<?php

namespace App\Filament\Resources\UnitUsers\Widgets;

use App\Policies\UnitUserPolicy;
use App\Services\UnitUserWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\Auth;

class ResidentsAgeChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = 'Residents by Age Group';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return UnitUserPolicy::hasDashboardAccess(Auth::user());
    }

    protected function getData(): array
    {
        $orderedGroups = ['06-12', '13-22', '23-30', '31-40', '41-50', '51-60', '61-70', '>70', 'Not Set'];
        $colorMap = WidgetColorPalette::unitUserAgeGroupColors();
        $user = Auth::user();

        $results = collect(
            UnitUserWidgetDataService::getScopedDataForUser($user, $this->tableFilters ?? [])['age_group_counts'] ?? []
        );

        return [
            'labels' => $orderedGroups,
            'datasets' => [
                [
                    'label' => 'Residents Age Group',
                    'data' => array_map(fn (string $group) => (int) ($results[$group] ?? 0), $orderedGroups),
                    'backgroundColor' => array_map(fn (string $group) => $colorMap[$group], $orderedGroups),
                    'hoverOffset' => 4,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
