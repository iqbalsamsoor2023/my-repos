<?php

namespace App\Filament\Resources\Pets\Widgets;

use App\Policies\PetPolicy;
use App\Services\PetWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class PetsAgeChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    public function getHeading(): string|Htmlable|null
    {
        return __('pet.widgets.age_chart_heading');
    }

    public static function canView(): bool
    {
        return PetPolicy::hasAdminDashboardAccess(Auth::user());
    }

    protected function getData(): array
    {
        $data = PetWidgetDataService::getScopedDataForUser(Auth::user(), $this->tableFilters ?? []);
        $distribution = $data['age_distribution'] ?? [];

        $labelsByKey = [
            'lt_2' => __('pet.widgets.age_lt_2'),
            'lt_4' => __('pet.widgets.age_lt_4'),
            'lt_6' => __('pet.widgets.age_lt_6'),
            'lt_8' => __('pet.widgets.age_lt_8'),
            'lt_10' => __('pet.widgets.age_lt_10'),
            'lt_12' => __('pet.widgets.age_lt_12'),
            'lt_14' => __('pet.widgets.age_lt_14'),
            'lt_16' => __('pet.widgets.age_lt_16'),
            'lt_18' => __('pet.widgets.age_lt_18'),
            'lt_20' => __('pet.widgets.age_lt_20'),
            'not_set' => __('pet.not_yet_set'),
        ];

        $colorMap = WidgetColorPalette::petAgeColors();

        $labels = [];
        $values = [];
        $colors = [];

        foreach ($labelsByKey as $key => $label) {
            $labels[] = $label;
            $values[] = (int) ($distribution[$key] ?? 0);
            $colors[] = $colorMap[$key] ?? WidgetColorPalette::neutralHex();
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('pet.widgets.number_of_pets'),
                    'data' => $values,
                    'backgroundColor' => $colors,
                    'hoverOffset' => 4,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
