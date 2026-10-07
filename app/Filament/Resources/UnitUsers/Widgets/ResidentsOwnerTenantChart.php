<?php

namespace App\Filament\Resources\UnitUsers\Widgets;

use App\Policies\UnitUserPolicy;
use App\Services\UnitUserWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\Auth;

class ResidentsOwnerTenantChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = 'Residents by Ownership Status';

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return UnitUserPolicy::hasDashboardAccess(Auth::user());
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $colors = WidgetColorPalette::unitUserOwnerTenantColors();
        $data = UnitUserWidgetDataService::getScopedDataForUser($user, $this->tableFilters ?? []);

        return [
            'labels' => ['Owner', 'Tenant'],
            'datasets' => [
                [
                    'label' => 'Ownership Status',
                    'data' => [
                        (int) ($data['ownership_counts']['owner'] ?? 0),
                        (int) ($data['ownership_counts']['tenant'] ?? 0),
                    ],
                    'backgroundColor' => [$colors['owner'], $colors['tenant']],
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
