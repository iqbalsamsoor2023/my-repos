<?php

namespace App\Filament\Resources\UnitUsers\Widgets;

use App\Policies\UnitUserPolicy;
use App\Services\UnitUserWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\Auth;

class ResidentsAllActionCancelledServiceChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = 'Residents by Residence Activation Status';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return UnitUserPolicy::isGlobalAdmin(Auth::user());
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $colors = WidgetColorPalette::unitUserActivationStatusColors();
        $data = UnitUserWidgetDataService::getScopedDataForUser($user, $this->tableFilters ?? [])['activation_status_counts'] ?? [];

        return [
            'datasets' => [
                [
                    'label' => 'Number of Resident Records',
                    'data' => [(int) ($data[5] ?? 0), (int) ($data[2] ?? 0)],
                    'backgroundColor' => [$colors['active'], $colors['inactive']],
                    'borderWidth' => 1,
                ],
            ],
            'labels' => ['Active - All Action', 'Inactive - Cancelled Service'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
