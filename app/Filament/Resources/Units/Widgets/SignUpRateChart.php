<?php

namespace App\Filament\Resources\Units\Widgets;

use App\Policies\UnitPolicy;
use App\Services\UnitWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\Auth;

class SignUpRateChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = 'Sign Up Rate';

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

        $colors = WidgetColorPalette::unitSignUpColors();

        $totalUnits = (int) $data['total_units'];
        $signedUnits = (int) $data['signed_up_count'];
        $notSignedUnits = max($totalUnits - $signedUnits, 0);

        return [
            'labels' => ['Signed Up', 'Not Yet Signed Up'],
            'datasets' => [
                [
                    'label' => 'Sign Up Rate',
                    'data' => [$signedUnits, $notSignedUnits],
                    'backgroundColor' => [$colors['signed_up'], $colors['not_signed_up']],
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
