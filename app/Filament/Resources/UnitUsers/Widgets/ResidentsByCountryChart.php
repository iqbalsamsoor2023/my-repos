<?php

namespace App\Filament\Resources\UnitUsers\Widgets;

use App\Policies\UnitUserPolicy;
use App\Services\UnitUserWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\Auth;

class ResidentsByCountryChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = 'Residents by Country';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return UnitUserPolicy::hasDashboardAccess(Auth::user());
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $countryTotals = UnitUserWidgetDataService::getScopedDataForUser($user, $this->tableFilters ?? [])['country_counts'] ?? [];

        $colorPalette = WidgetColorPalette::unitUserCountrySeriesColors();
        $notSetColor = WidgetColorPalette::unitUserNotSetHex();

        $colors = [];
        $index = 0;

        foreach ($countryTotals as $label => $_) {
            $colors[] = $label === 'Not Yet Set' ? $notSetColor : $colorPalette[$index++ % count($colorPalette)];
        }

        $displayLabels = array_map(fn (string $label): string => $label === 'Not Yet Set' ? __('user.not_yet_set') : $label, array_keys($countryTotals));

        return [
            'labels' => $displayLabels,
            'datasets' => [
                [
                    'label' => 'Residents by Country',
                    'data' => array_values($countryTotals),
                    'backgroundColor' => $colors,
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
