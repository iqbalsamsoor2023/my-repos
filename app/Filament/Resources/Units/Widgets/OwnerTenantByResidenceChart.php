<?php

namespace App\Filament\Resources\Units\Widgets;

use App\Policies\UnitPolicy;
use App\Services\UnitWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\Auth;

class OwnerTenantByResidenceChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = 'Owner vs Tenant Distribution';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return UnitPolicy::isPropertyManagerOrCenter(Auth::user());
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $filters = $this->tableFilters ?? [];
        $data = UnitWidgetDataService::getDashboardDataForUser($user, $filters) ?? UnitWidgetDataService::empty();
        $colors = WidgetColorPalette::unitOwnerTenantColors();

        return [
            'datasets' => [[
                'label' => 'Owner vs Tenant',
                'data' => [(int) $data['owner_count'], (int) $data['tenant_count']],
                'backgroundColor' => [$colors['owner'], $colors['tenant']],
                'hoverOffset' => 4,
            ]],
            'labels' => ['Occupied by Owner', 'Occupied by Tenant'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
