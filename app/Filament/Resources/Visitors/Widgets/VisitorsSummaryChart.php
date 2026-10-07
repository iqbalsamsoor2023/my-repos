<?php

namespace App\Filament\Resources\Visitors\Widgets;

use App\Enums\User\RoleType;
use App\Filament\Resources\Visitors\Pages\ListVisitors;
use App\Filament\Resources\Visitors\Widgets\Concerns\HasDateFilter;
use App\Filament\Resources\Visitors\Widgets\Concerns\ResolvesVmsResidence;
use App\Services\VmsAnalyticsQueryService;
use App\Support\WidgetColorPalette;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VisitorsSummaryChart extends ChartWidget
{
    use HasDateFilter, InteractsWithPageTable, ResolvesVmsResidence;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '250px';

    protected ?string $pollingInterval = null;

    public ?string $filter = 'month';

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
        [$from, $until] = $this->filterDates();

        if ($from || $until) {
            return "Visitor Trend ({$from} → {$until})";
        }

        if ($this->isPmView()) {
            return match ($this->filter) {
                'minute' => 'Visitor Trend — Last 60 Minutes',
                'hour' => 'Visitor Trend — Today by Hour',
                default => 'Visitor Trend — This Month',
            };
        }

        return 'Visitor Summary Trend';
    }

    protected function getFilters(): ?array
    {
        [$from, $until] = $this->filterDates();

        if (($from || $until) && ! VmsAnalyticsQueryService::isArchiveRange($from, $until)) {
            return null;
        }

        if ($this->isPmView()) {
            return [
                'day' => 'This Month',
                'hour' => 'Today (hourly)',
                'minute' => 'Last 60 min',
            ];
        }

        return [
            'month' => 'Last 30 Days',
            'week' => 'Last 7 Days',
            'day' => 'Yesterday',
        ];
    }

    protected function getData(): array
    {
        if ($this->usesFilteredTableQuery()) {
            $trend = $this->trendFromTableQuery();
            $data = array_values($trend);
            $labels = array_map(fn ($d) => Carbon::parse($d)->format('M d'), array_keys($trend));
            $label = $this->isPmView() ? 'Visitors In' : 'Visitor Entries';

            return $this->dataset($label, $data, $labels, count($data) === 1 ? 4 : 0);
        }

        [$from, $until] = $this->filterDates();

        if (VmsAnalyticsQueryService::isArchiveRange($from, $until)) {
            $from = null;
            $until = null;
        }

        if ($this->isPmView()) {
            return $this->pmData($from, $until);
        }

        return $this->superAdminData($from, $until);
    }

    protected function trendFromTableQuery(): array
    {
        $baseQuery = $this->getPageTableQuery()->reorder()->toBase();
        $cacheKey = 'vms:widget:visitor_trend:'.md5($baseQuery->toSql().json_encode($baseQuery->getBindings()));

        return Cache::remember($cacheKey, 60, function () use ($baseQuery) {
            return DB::query()
                ->fromSub($baseQuery, 'visitor_scope')
                ->selectRaw('DATE(created_at) as bucket, COUNT(*) as total')
                ->groupBy('bucket')
                ->orderBy('bucket')
                ->pluck('total', 'bucket')
                ->map(fn ($v) => (int) $v)
                ->all();
        });
    }

    protected function pmData(?string $from, ?string $until): array
    {
        $residenceId = $this->pmResidenceId();

        if (! $residenceId) {
            return ['datasets' => [['label' => 'No data', 'data' => []]], 'labels' => []];
        }

        if ($from || $until) {
            $resolvedFrom = $from ?? now()->startOfMonth()->toDateString();
            $resolvedUntil = $until ?? now()->toDateString();
            $trend = VmsAnalyticsQueryService::summaryTrend($resolvedFrom, $resolvedUntil, $residenceId);
        } elseif (in_array($this->filter, ['hour', 'minute'], true)) {
            $trend = VmsAnalyticsQueryService::summaryTrendLive($residenceId, $this->filter);

            return $this->dataset('Visitors In', array_values($trend), array_keys($trend), count($trend) <= 2 ? 4 : 0);
        } else {
            $trend = VmsAnalyticsQueryService::summaryTrend(null, null, $residenceId);
        }

        $labels = array_map(fn ($d) => Carbon::parse($d)->format('M d'), array_keys($trend));
        $data = array_values($trend);

        return $this->dataset('Visitors In', $data, $labels, count($data) <= 2 ? 4 : 0);
    }

    protected function superAdminData(?string $from, ?string $until): array
    {
        $cached = VmsAnalyticsQueryService::summaryTrend($from, $until);

        if ($from || $until) {
            $data = array_values($cached);
            $labels = array_map(fn ($d) => Carbon::parse($d)->format('M d'), array_keys($cached));

            return $this->dataset('Visitor Entries', $data, $labels, count($data) === 1 ? 4 : 0);
        }

        $filter = $this->filter ?? 'month';

        if (! is_array($cached) || empty($cached) || ! isset($cached['month'])) {
            [$rangeFrom, $rangeUntil] = match ($filter) {
                'day' => [now()->subDay()->toDateString(), now()->subDay()->toDateString()],
                'week' => [now()->subDays(6)->toDateString(), now()->toDateString()],
                default => [now()->subDays(29)->toDateString(), now()->toDateString()],
            };

            $trendData = VmsAnalyticsQueryService::summaryTrend($rangeFrom, $rangeUntil);
            $data = array_map('intval', array_values($trendData));
            $labels = array_map(fn ($d) => Carbon::parse($d)->format('M d'), array_keys($trendData));

            return $this->dataset('Visitor Entries', $data, $labels, count($data) <= 1 ? 4 : 0);
        }

        if ($filter === 'day') {
            return $this->dataset('Visitor Entries', [$cached['yesterday'] ?? 0], [now()->subDay()->format('M d')], 4);
        }

        $trendData = $cached[$filter] ?? $cached['month'] ?? [];
        $data = array_map('intval', array_values($trendData));
        $labels = array_map(fn ($d) => Carbon::parse($d)->format('M d'), array_keys($trendData));

        return $this->dataset('Visitor Entries', $data, $labels, count($data) === 1 ? 4 : 0);
    }

    protected function dataset(string $label, array $data, array $labels, int $pointRadius): array
    {
        return [
            'datasets' => [[
                'label' => $label,
                'data' => $data,
                'backgroundColor' => WidgetColorPalette::trendLineFill(),
                'borderColor' => WidgetColorPalette::trendLine(),
                'tension' => 0.35,
                'pointRadius' => $pointRadius,
                'fill' => true,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getTablePage(): string
    {
        return ListVisitors::class;
    }
}
