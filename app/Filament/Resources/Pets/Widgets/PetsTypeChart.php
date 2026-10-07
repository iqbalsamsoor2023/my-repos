<?php

namespace App\Filament\Resources\Pets\Widgets;

use App\Policies\PetPolicy;
use App\Services\PetWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class PetsTypeChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $pollingInterval = null;

    public function getHeading(): string|Htmlable|null
    {
        return __('pet.widgets.type_chart_heading');
    }

    public static function canView(): bool
    {
        return PetPolicy::hasDashboardAccess(Auth::user());
    }

    protected function getData(): array
    {
        $data = PetWidgetDataService::getScopedDataForUser(Auth::user(), $this->tableFilters ?? []);
        $typeCounts = $data['type_counts'] ?? [];
        $colorMap = WidgetColorPalette::petTypeColors();

        $labels = [
            __('pet.dog'),
            __('pet.cat'),
            __('pet.not_yet_set'),
        ];

        $values = [
            $typeCounts['dog'] ?? 0,
            $typeCounts['cat'] ?? 0,
            $typeCounts['not_set'] ?? 0,
        ];

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('pet.widgets.number_of_pets'),
                    'data' => $values,
                    'backgroundColor' => [
                        $colorMap['dog'],
                        $colorMap['cat'],
                        $colorMap['not_set'],
                    ],
                    'borderColor' => [
                        $colorMap['dog'],
                        $colorMap['cat'],
                        $colorMap['not_set'],
                    ],
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
