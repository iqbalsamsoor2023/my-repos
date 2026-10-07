<?php

namespace App\Helpers;

use Filament\Widgets\StatsOverviewWidget\Stat;

class ChartHelper
{
    //handle UI for empty doughnut chart
    public static function emptyDoughnut(string $label = 'No Data', string $message = 'No data available'): array
    {
        return [
            'datasets' => [
                [
                    'label' => $label,
                    'data' => [1],
                    'backgroundColor' => ['#E5E7EB'], // Neutral gray
                    'borderWidth' => 1,
                ],
            ],
            'labels' => [$message],
        ];
    }

    // for empty stat overview UI
    public static function emptyStats(string $title = 'No Data', string $description = 'No data found'): array
    {
        return [
            Stat::make($title, '0')->description($description),
        ];
    }
}
