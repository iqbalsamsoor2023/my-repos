<?php

namespace App\Filament\Resources\Visitors\Widgets;

use App\Enums\User\RoleType;
use App\Filament\Resources\Visitors\Pages\ListVisitors;
use App\Filament\Resources\Visitors\Widgets\Concerns\HasDateFilter;
use App\Filament\Resources\Visitors\Widgets\Concerns\ResolvesVmsResidence;
use App\Services\VmsAnalyticsQueryService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VisitorStatsOverview extends BaseWidget
{
    use HasDateFilter, InteractsWithPageTable, ResolvesVmsResidence;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return Auth::user()?->hasAnyRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::ADMIN->value,
            RoleType::PROPERTY_MANAGEMENT->value,
        ]) ?? false;
    }

    public function getHeading(): string
    {
        return $this->periodHeading('Visitor Summary');
    }

    protected function getStats(): array
    {
        $residenceId = $this->pmResidenceId();
        $colors = WidgetColorPalette::visitorStats();
        $toPercent = static fn (int $v, int $t): string => $t > 0 ? number_format(($v / $t) * 100, 1).'%' : '0.0%';

        if ($this->isPmView() && ! $residenceId) {
            return [];
        }

        if ($this->hasNonDateFilters()) {
            $data = $this->countersFromTableQuery();
        } else {
            [$resolvedFrom, $resolvedUntil] = $this->resolvedDates();
            $data = VmsAnalyticsQueryService::globalStats($resolvedFrom, $resolvedUntil, $residenceId);
        }

        $in = $data['visitors_in'] ?? 0;
        $out = $data['visitors_out'] ?? 0;
        $rem = $data['visitors_remaining'] ?? 0;
        $night = $data['visitors_overnight'] ?? 0;

        return [
            Stat::make('In', number_format($in))
                ->description(__('visitor.total_visitors_checked_in'))
                ->color(WidgetColorPalette::statColorFromHex($colors[0]))
                ->chartColor($colors[0]),

            Stat::make('Out', number_format($out))
                ->description($toPercent($out, $in).' of total in')
                ->color(WidgetColorPalette::statColorFromHex($colors[1]))
                ->chartColor($colors[1]),

            Stat::make('Remaining', number_format($rem))
                ->description($toPercent($rem, $in).' of total in')
                ->color(WidgetColorPalette::statColorFromHex($colors[2]))
                ->chartColor($colors[2]),

            Stat::make('Overnight', number_format($night))
                ->description($toPercent($night, $in).' of total in')
                ->color(WidgetColorPalette::statColorFromHex($colors[3]))
                ->chartColor($colors[3]),
        ];
    }

    protected function countersFromTableQuery(): array
    {
        $baseQuery = $this->getPageTableQuery()->reorder()->toBase();
        $cacheKey = 'vms:widget:visitor_stats_overview:'.md5(
            $baseQuery->toSql().json_encode($baseQuery->getBindings())
        );

        return Cache::remember($cacheKey, 60, function () use ($baseQuery) {
            $row = DB::query()
                ->fromSub($baseQuery, 'visitor_scope')
                ->selectRaw('COUNT(*) as visitors_in')
                ->selectRaw('SUM(CASE WHEN arrival_time IS NOT NULL AND leave_time IS NOT NULL THEN 1 ELSE 0 END) as visitors_out')
                ->selectRaw('SUM(CASE WHEN arrival_time IS NOT NULL AND leave_time IS NULL THEN 1 ELSE 0 END) as visitors_remaining')
                ->selectRaw('SUM(CASE WHEN arrival_time IS NOT NULL AND (
                    (leave_time IS NULL AND arrival_time < ?)
                    OR (leave_time IS NOT NULL AND DATE(leave_time) > DATE(arrival_time))
                ) THEN 1 ELSE 0 END) as visitors_overnight', [today()])
                ->first();

            return [
                'visitors_in' => (int) ($row->visitors_in ?? 0),
                'visitors_out' => (int) ($row->visitors_out ?? 0),
                'visitors_remaining' => (int) ($row->visitors_remaining ?? 0),
                'visitors_overnight' => (int) ($row->visitors_overnight ?? 0),
            ];
        });
    }

    protected function getTablePage(): string
    {
        return ListVisitors::class;
    }
}
