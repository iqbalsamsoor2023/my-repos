<?php

namespace App\Filament\Resources\Residences\Widgets;

use App\Services\ResidenceWidgetDataService;
use App\Models\ResidenceActivationStatus;
use App\Traits\ResidenceWidgetFilters;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class MoobanActivationStatusStatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageTable, ResidenceWidgetFilters;

    protected int|string|array $columnSpan = 2;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $shared = ResidenceWidgetDataService::getAllData($this->tableFilters ?? []);
        $statusCounts = $shared['activation_status'] ?? [];
        $total = $shared['total'] ?? array_sum($statusCounts);

        $statuses = ResidenceActivationStatus::all();

        return $statuses->map(function ($status) use ($statusCounts, $total) {
            $label = $status->status;
            $count = $statusCounts[$label] ?? 0;

            $percentage = $total > 0
                ? number_format(($count / $total) * 100, 2)
                : '0.00';

            $color = $this->getColorForStatus($label);

            return Stat::make(
                new HtmlString("<span style=\"color: {$color}; font-weight: 600;\">{$label}</span>"),
                new HtmlString("
                    <div style=\"display:flex; align-items:baseline; gap:0.5rem;\">
                        <span style=\"color: {$color}; font-size:1.75rem; font-weight:700;\">{$count}</span>
                    </div>
                ")
            )
            ->description(new HtmlString(
                '<span>
                    <strong class="text-black dark:text-white">' . $percentage . '%</strong> of ' . $total . '
                </span>'
            ));            
        })->toArray();
    }

    protected function getColorForStatus(string $status): string
    {
        return match ($status) {
            'Inactive - Demo'               => '#95a5a6', // Gray
            'Inactive - Cancelled Service'  => '#e74c3c', // Red
            'Active - GT Only'              => '#f52071', // Pink
            'Active - GP Only'              => '#3498db', // Blue
            'Active - All Action'           => '#2ecc71', // Green
            'Active - For Demo Only'        => '#f39c12', // Orange
            default                         => '#6b7280', // Fallback gray
        };
    }

    public function updatedTableFilters(): void
    {
        $this->emitSelf('refresh');
    }
}