<?php

namespace App\Filament\Resources\UnitUsers\Widgets;

use App\Enums\User\Gender;
use App\Policies\UnitUserPolicy;
use App\Services\UnitUserWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\Auth;

class ResidentsGenderChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = 'Residents by Gender';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return UnitUserPolicy::hasDashboardAccess(Auth::user());
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $data = UnitUserWidgetDataService::getScopedDataForUser($user, $this->tableFilters ?? []);
        $colors = WidgetColorPalette::unitUserGenderColors();

        return $this->formatChart([
            Gender::MALE->value => (int) ($data['gender_counts'][Gender::MALE->value] ?? 0),
            Gender::FEMALE->value => (int) ($data['gender_counts'][Gender::FEMALE->value] ?? 0),
            'null' => (int) ($data['gender_counts']['null'] ?? 0),
        ], $colors);
    }

    protected function formatChart(array $counts, array $colors): array
    {
        return [
            'labels' => ['Male', 'Female', __('user.not_yet_set')],
            'datasets' => [
                [
                    'label' => 'Residents by Gender',
                    'data' => [
                        $counts[Gender::MALE->value] ?? 0,
                        $counts[Gender::FEMALE->value] ?? 0,
                        $counts['null'] ?? 0,
                    ],
                    'backgroundColor' => [$colors['male'], $colors['female'], $colors['not_set']],
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
