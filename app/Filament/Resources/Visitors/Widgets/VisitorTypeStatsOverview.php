<?php

namespace App\Filament\Resources\Visitors\Widgets;

use App\Enums\User\RoleType;
use App\Enums\Visitor\ArrivalType;
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

class VisitorTypeStatsOverview extends BaseWidget
{
    use HasDateFilter, InteractsWithPageTable, ResolvesVmsResidence;

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1, 'xl' => 2];

    protected ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 3;
    }

    public function getHeading(): ?string
    {
        return $this->periodHeading('Visitor Type');
    }

    public static function canView(): bool
    {
        return Auth::user()?->hasAnyRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::ADMIN->value,
            RoleType::PROPERTY_MANAGEMENT->value,
        ]) ?? false;
    }

    protected function getStats(): array
    {
        $residenceId = $this->pmResidenceId();
        $colors = WidgetColorPalette::visitorType();
        $typeLabels = [
            ArrivalType::DRIVE_IN->getLabel(),
            ArrivalType::WALK_IN->getLabel(),
            'Prebook',
        ];

        if ($this->isPmView() && ! $residenceId) {
            return [];
        }

        if ($this->usesFilteredTableQuery()) {
            $data = $this->typeCountsFromTableQuery();
        } else {
            [$resolvedFrom, $resolvedUntil] = $this->resolvedDates();
            $data = VmsAnalyticsQueryService::globalStats($resolvedFrom, $resolvedUntil, $residenceId);
        }

        $counts = [
            (int) ($data['drive_in'] ?? 0),
            (int) ($data['walk_in'] ?? 0),
            (int) ($data['prebook'] ?? 0),
        ];
        $total = array_sum($counts);
        $toPercent = static fn (int $v): string => $total > 0 ? number_format(($v / $total) * 100, 1).'%' : '0.0%';

        $stats = [];
        foreach ($counts as $i => $count) {
            $stats[] = Stat::make($typeLabels[$i], number_format($count))
                ->description($toPercent($count).' of total')
                ->color(WidgetColorPalette::statColorFromHex($colors[$i]))
                ->chartColor($colors[$i]);
        }

        return $stats;
    }

    protected function typeCountsFromTableQuery(): array
    {
        $baseQuery = $this->getPageTableQuery()->reorder()->toBase();
        $cacheKey = 'vms:widget:visitor_type:'.md5($baseQuery->toSql().json_encode($baseQuery->getBindings()));

        return Cache::remember($cacheKey, 60, function () use ($baseQuery) {
            $row = DB::query()
                ->fromSub($baseQuery, 'visitor_scope')
                ->selectRaw('SUM(CASE WHEN is_pre_register = 0 AND arrival_type = ? THEN 1 ELSE 0 END) as drive_in', [ArrivalType::DRIVE_IN->value])
                ->selectRaw('SUM(CASE WHEN is_pre_register = 0 AND arrival_type = ? THEN 1 ELSE 0 END) as walk_in', [ArrivalType::WALK_IN->value])
                ->selectRaw('SUM(CASE WHEN is_pre_register = 1 THEN 1 ELSE 0 END) as prebook')
                ->first();

            return [
                'drive_in' => (int) ($row->drive_in ?? 0),
                'walk_in' => (int) ($row->walk_in ?? 0),
                'prebook' => (int) ($row->prebook ?? 0),
            ];
        });
    }

    protected function getTablePage(): string
    {
        return ListVisitors::class;
    }
}
