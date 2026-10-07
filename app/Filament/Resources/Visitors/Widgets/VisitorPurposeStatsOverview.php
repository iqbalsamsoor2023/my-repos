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

class VisitorPurposeStatsOverview extends BaseWidget
{
    use HasDateFilter, InteractsWithPageTable, ResolvesVmsResidence;

    private const MAX_SEGMENTS = 7;

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1, 'xl' => 2];

    protected ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 3;
    }

    public function getHeading(): ?string
    {
        return $this->periodHeading('Purpose of Visit');
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

        if ($this->isPmView() && ! $residenceId) {
            return [];
        }

        if ($this->usesFilteredTableQuery()) {
            $purposeData = $this->purposeFromTableQuery();
        } else {
            [$resolvedFrom, $resolvedUntil] = $this->resolvedDates();
            $purposeData = VmsAnalyticsQueryService::purposeBreakdown($resolvedFrom, $resolvedUntil, $residenceId);
        }

        $topPurposes = array_slice($purposeData, 0, self::MAX_SEGMENTS, true);
        $othersCount = array_sum(array_slice($purposeData, self::MAX_SEGMENTS));

        $data = collect($topPurposes);
        if ($othersCount > 0) {
            $data = $data->merge(['Others' => $othersCount]);
        }

        $total = $data->sum();

        if ($total === 0) {
            return [
                Stat::make('No Data', '0')
                    ->description(__('visitor.no_purpose_records'))
                    ->color(WidgetColorPalette::statColorFromHex('#64748B')),
            ];
        }

        $colors = WidgetColorPalette::visitorPurpose();
        $stats = [];
        $keys = $data->keys()->toArray();
        $vals = $data->values()->toArray();

        foreach ($vals as $i => $count) {
            $stats[] = Stat::make($keys[$i], number_format($count))
                ->description(number_format(($count / $total) * 100, 1).'%')
                ->color(WidgetColorPalette::statColorFromHex($colors[$i] ?? '#64748B'))
                ->chartColor($colors[$i] ?? '#64748B');
        }

        return $stats;
    }

    protected function purposeFromTableQuery(): array
    {
        $baseQuery = $this->getPageTableQuery()->reorder()->toBase();
        $cacheKey = 'vms:widget:visitor_purpose:'.md5($baseQuery->toSql().json_encode($baseQuery->getBindings()));

        $data = Cache::remember($cacheKey, 60, function () use ($baseQuery) {
            return DB::query()
                ->fromSub($baseQuery, 'visitor_scope')
                ->whereNotNull('visitor_purpose')
                ->where('visitor_purpose', '<>', '')
                ->selectRaw('visitor_purpose, COUNT(*) as total')
                ->groupBy('visitor_purpose')
                ->pluck('total', 'visitor_purpose')
                ->map(fn ($v) => (int) $v)
                ->all();
        });

        return VmsAnalyticsQueryService::normalizePurposeBuckets($data);
    }

    protected function getTablePage(): string
    {
        return ListVisitors::class;
    }
}
