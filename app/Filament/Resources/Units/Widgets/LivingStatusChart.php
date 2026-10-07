<?php

namespace App\Filament\Resources\Units\Widgets;

use App\Enums\Unit\StatusType;
use App\Policies\UnitPolicy;
use App\Services\UnitWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\Auth;

class LivingStatusChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = 'Living Status Distribution';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return UnitPolicy::isPropertyManagerOrCenter(Auth::user());
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $filters = $this->tableFilters ?? [];

        $data = UnitWidgetDataService::getDashboardDataForUser($user, $filters);

        if ($data === null) {
            return ['labels' => [], 'datasets' => []];
        }

        $statusCounts = $data['status_counts'];
        $colorMap = WidgetColorPalette::unitStatusColors();

        $labels = [];
        $chartData = [];
        $colors = [];

        foreach (StatusType::cases() as $status) {
            $labels[] = $status->getLabel();
            $chartData[] = (int) ($statusCounts[$status->value] ?? 0);
            $colors[] = $colorMap[$status->value] ?? $colorMap['null'];
        }

        $nullCount = (int) ($statusCounts['null'] ?? 0);
        if ($nullCount > 0) {
            $labels[] = __('user.not_yet_set');
            $chartData[] = $nullCount;
            $colors[] = $colorMap['null'];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Living Status',
                    'data' => $chartData,
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
