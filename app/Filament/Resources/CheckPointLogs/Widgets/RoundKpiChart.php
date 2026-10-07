<?php

namespace App\Filament\Resources\CheckPointLogs\Widgets;

use App\Models\Sgoc\Round;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class RoundKpiChart extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 'full';

    protected int|array|null $columns = 5;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $user = auth()->user();
        $startDate = $this->tableFilters['created_at']['created_from'] ?? Carbon::now()->toDateString();
        $endDate = $this->tableFilters['created_at']['created_until'] ?? Carbon::now()->toDateString();
        $userId = $this->tableFilters['userId'] ?? 0;
        $residenceId = $this->tableFilters['residences']['residence'] ?? ($user->propertyManagement->id ?? null);
        $checkpointId = $this->tableFilters['checkpointId'] ?? 0;

        $query = Round::query()
            ->select([
                DB::raw('COUNT(cr.id) as total_checkpoints'),
                DB::raw('SUM(CASE
                    WHEN cl.id IS NOT NULL AND cl.status = 1 THEN 1
                    ELSE 0
                END) as passed_count'),
                DB::raw('SUM(CASE
                    WHEN cl.id IS NOT NULL AND cl.status = 2 THEN 1
                    ELSE 0
                END) as skipped_count'),
                DB::raw('SUM(CASE
                    WHEN cl.id IS NOT NULL AND cl.status = 3 THEN 1
                    ELSE 0
                END) as not_passed_count'),
                DB::raw('SUM(CASE
                    WHEN cl.id IS NOT NULL AND cl.status = 4 THEN 1
                    ELSE 0
                END) as missed_count'),
            ])
            ->join('checkpoint_round as cr', 'cr.round_id', '=', 'rounds.id')
            ->join('checkpoints as c', 'c.id', '=', 'cr.checkpoint_id')
            ->leftJoin('checkpoint_logs as cl', function ($join) use ($startDate, $endDate) {
                $join->on('cl.checkpoint_round_id', '=', 'cr.id')
                    ->whereDate('cl.created_at', '>=', $startDate)
                    ->whereDate('cl.created_at', '<=', $endDate);
            })
            ->whereNull('cr.deleted_at')
            ->when($userId, fn ($q) => $q->where('rounds.user_id', $userId))
            ->when($residenceId, fn ($q) => $q->where('c.mmb_residence_id', $residenceId))
            ->when($checkpointId, fn ($q) => $q->where('c.id', $checkpointId));

        $result = $query->first();

        $totalCheckpoints = $result->total_checkpoints ?? 0;
        $passedCount = $result->passed_count ?? 0;
        $notPassedCount = $result->not_passed_count ?? 0;
        $skippedCount = $result->skipped_count ?? 0;
        $missedCount = $result->missed_count ?? 0;

        // Calculate KPI percentages
        $passedKpi = $totalCheckpoints > 0 ? round(($passedCount / $totalCheckpoints) * 100, 2) : 0;
        $notPassedKpi = $totalCheckpoints > 0 ? round(($notPassedCount / $totalCheckpoints) * 100, 2) : 0;
        $skippedKpi = $totalCheckpoints > 0 ? round(($skippedCount / $totalCheckpoints) * 100, 2) : 0;
        $missedKpi = $totalCheckpoints > 0 ? round(($missedCount / $totalCheckpoints) * 100, 2) : 0;

        $statsDataList = [
            [
                'label' => 'Passed',
                'count' => $passedCount,
                'kpi' => $passedKpi,
                'color' => '#28a745',
                'icon' => 'heroicon-o-check-circle',
            ],
            [
                'label' => 'Not Passed',
                'count' => $notPassedCount,
                'kpi' => $notPassedKpi,
                'color' => '#54A8D1',
                'icon' => 'heroicon-o-x-circle',
            ],
            [
                'label' => 'Skipped',
                'count' => $skippedCount,
                'kpi' => $skippedKpi,
                'color' => '#C8520C',
                'icon' => 'heroicon-o-forward',
            ],
            [
                'label' => 'Missed',
                'count' => $missedCount,
                'kpi' => $missedKpi,
                'color' => '#d14755',
                'icon' => 'heroicon-o-exclamation-circle',
            ],
        ];

        $stats = [
            Stat::make('Total Checkpoints', $totalCheckpoints)
                ->description(__('checkpoint.total_checkpoints_description'))
                ->color('gray')
                ->icon('heroicon-o-map-pin'),
        ];

        foreach ($statsDataList as $data) {
            $stats[] = $this->createStat(
                $data['label'],
                $data['count'],
                $data['kpi'],
                $data['color'],
                $data['icon']
            );
        }

        return $stats;
    }

    protected function createStat(string $label, int $count, float $kpi, string $color, string $icon): Stat
    {
        return Stat::make(
            new HtmlString("<span style=\"color:{$color};font-weight:bold;\">{$label}</span>"),
            $count
        )
            ->icon($icon)
            ->value(new HtmlString("
                <div style='margin-top:6px; width:100%'>
                    <div style=\"color:{$color};\">
                        {$count}
                    </div>
                    <div>
                        <div style='display:flex; justify-content:space-between; font-size:0.75rem; font-weight:600; margin-bottom:4px;'>
                            <span>KPI</span>
                            <span style='font-size: 0.75rem; font-weight: 600;'>{$kpi}%</span>
                        </div>
                        <div style='height:6px; width:100%; background:#e5e7eb; border-radius:9999px; overflow:hidden'>
                            <div
                                style='height:100%; width:{$kpi}%; background:{$color}; border-radius:9999px; transition:width .4s'
                            ></div>
                        </div>
                    </div>
                </div>
            "));
    }
}
