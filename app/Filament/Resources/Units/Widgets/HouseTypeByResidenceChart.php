<?php

namespace App\Filament\Resources\Units\Widgets;

use App\Enums\Unit\HouseType;
use App\Policies\UnitPolicy;
use App\Services\UnitWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\Auth;

class HouseTypeByResidenceChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = 'House Type Distribution';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return UnitPolicy::isPropertyManager(Auth::user());
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $filters = $this->tableFilters ?? [];

        $data = UnitWidgetDataService::getDashboardDataForUser($user, $filters);

        if ($data === null) {
            return ['labels' => [], 'datasets' => []];
        }

        $houseTypeSubType = $data['house_type_sub_type'];
        $notSetCount = $data['house_type_null_count'];

        // Derive per-house_type total by summing sub_type counts
        $counts = [];
        foreach ($houseTypeSubType as $ht => $stCounts) {
            $counts[(int) $ht] = array_sum($stCounts);
        }

        $labels = [];
        $chartData = [];
        $colors = [];

        foreach (HouseType::cases() as $case) {
            $count = $counts[$case->value] ?? 0;
            if ($count <= 0) {
                continue;
            }
            $labels[] = $case->label();
            $chartData[] = $count;
            $colors[] = WidgetColorPalette::unitHouseTypeHex($case->value);
        }

        if ($notSetCount > 0) {
            $labels[] = __('user.not_yet_set');
            $chartData[] = $notSetCount;
            $colors[] = WidgetColorPalette::unitHouseTypeHex('null');
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'House Type',
                'data' => $chartData,
                'backgroundColor' => $colors,
                'hoverOffset' => 4,
            ]],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
