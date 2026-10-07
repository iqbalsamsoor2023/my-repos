<?php

namespace App\Filament\Resources\CheckPointLogs\Widgets;

use App\Enums\Checkpoint\CheckpointLogStatus;
use App\Models\CheckpointLog;
use App\Models\Sgoc\Round;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class RoundProgressWidget extends BaseWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 'full';

    protected int|array|null $columns = 4;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $user = auth()->user();
        $startDate = $this->tableFilters['created_at']['created_from'] ?? Carbon::now()->toDateString();
        $endDate = $this->tableFilters['created_at']['created_until'] ?? Carbon::now()->toDateString();
        $residenceId = $this->tableFilters['residences']['residence'] ?? ($user->propertyManagement->id ?? null);

        $query = CheckpointLog::with('checkpointRound.round')
            ->whereHas('checkpointRound.round', function ($q) use ($residenceId) {
                $q->when($residenceId, fn ($q) => $q->where('mmb_residence_id', $residenceId));
            });

        $totalRounds = (int) (clone $query)->count();

        $now = Carbon::now();
        $today = $now->toDateString();
        $isPast = $endDate < $today;

        if ($isPast) {
            // For past dates, retrieve counts from checkpoint_logs grouped by date + round_id
            $baseQuery = CheckpointLog::query()
                ->join('checkpoint_round', 'checkpoint_round.id', '=', 'checkpoint_logs.checkpoint_round_id')
                ->join('rounds', 'rounds.id', '=', 'checkpoint_round.round_id')
                ->whereNull('checkpoint_round.deleted_at')
                ->whereDate('checkpoint_logs.created_at', '>=', $startDate)
                ->whereDate('checkpoint_logs.created_at', '<=', $endDate)
                ->when($residenceId, fn ($q) => $q->where('rounds.mmb_residence_id', $residenceId));

            $totalRounds = (clone $baseQuery)
                ->selectRaw('DATE(checkpoint_logs.created_at) as log_date, rounds.id as round_id')
                ->groupBy('log_date', 'round_id')
                ->get()
                ->count();

            $checkedCount = (clone $baseQuery)
                ->where('checkpoint_logs.status', '!=', CheckpointLogStatus::MISS->value)
                ->selectRaw('DATE(checkpoint_logs.created_at) as log_date, rounds.id as round_id')
                ->groupBy('log_date', 'round_id')
                ->get()
                ->count();

            $missedCount = (clone $baseQuery)
                ->where('checkpoint_logs.status', CheckpointLogStatus::MISS->value)
                ->selectRaw('DATE(checkpoint_logs.created_at) as log_date, rounds.id as round_id')
                ->groupBy('log_date', 'round_id')
                ->get()
                ->count();

            $waitingCount = 0;
        } else {
            // For today / future dates, use rounds as base
            $roundQuery = Round::query()
                ->when($residenceId, fn ($q) => $q->where('mmb_residence_id', $residenceId));

            $totalRounds = (int) (clone $roundQuery)->count();

            $checkedCount = (clone $roundQuery)
                ->whereHas('checkpointRounds.checkpointLogs', function ($q) use ($startDate, $endDate) {
                    $q->whereDate('created_at', '>=', $startDate)
                        ->whereDate('created_at', '<=', $endDate)
                        ->where('status', '!=', CheckpointLogStatus::MISS->value);
                })
                ->count();

            $waitingCount = (clone $roundQuery)
                ->where('start_time', '>', $now->format('H:i:s'))
                ->count();

            $missedCount = $totalRounds - ($waitingCount + $checkedCount);
        }

        $checkedPct = $totalRounds > 0 ? round(($checkedCount / $totalRounds) * 100, 1) : 0;
        $missedPct = $totalRounds > 0 ? round(($missedCount / $totalRounds) * 100, 1) : 0;
        $waitingPct = $totalRounds > 0 ? round(($waitingCount / $totalRounds) * 100, 1) : 0;

        return [
            Stat::make(
                new HtmlString('<span style="font-weight:bold;">Total Rounds</span>'),
                $totalRounds
            )
                ->icon('heroicon-o-arrow-path')
                ->description(__('checkpoint.scheduled_rounds_description')),

            $this->makeProgressStat(
                label: 'Checked',
                count: $checkedCount,
                pct: $checkedPct,
                color: '#28a745',
                icon: 'heroicon-o-check-badge',
            ),

            $this->makeProgressStat(
                label: 'Missed',
                count: $missedCount,
                pct: $missedPct,
                color: '#dc3545',
                icon: 'heroicon-o-x-circle',
            ),

            $this->makeProgressStat(
                label: 'Waiting',
                count: $waitingCount,
                pct: $waitingPct,
                color: '#ffc107',
                icon: 'heroicon-o-clock',
            ),
        ];
    }

    protected function makeProgressStat(string $label, int $count, float $pct, string $color, string $icon): Stat
    {
        return Stat::make(
            new HtmlString("<span style=\"color:{$color};font-weight:bold;\">{$label}</span>"),
            $count
        )
            ->icon($icon)
            ->value(new HtmlString("
                <div style='margin-top:6px; width:100%'>
                    <div style=\"color:{$color};\">{$count}</div>
                    <div>
                        <div style='display:flex; justify-content:space-between; font-size:0.75rem; font-weight:600; margin-bottom:4px;'>
                            <span>KPI</span>
                            <span>{$pct}%</span>
                        </div>
                        <div style='height:6px; width:100%; background:#e5e7eb; border-radius:9999px; overflow:hidden'>
                            <div style='height:100%; width:{$pct}%; background:{$color}; border-radius:9999px; transition:width .4s'></div>
                        </div>
                    </div>
                </div>
            "));
    }
}
