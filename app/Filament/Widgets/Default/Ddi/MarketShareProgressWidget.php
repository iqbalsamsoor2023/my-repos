<?php

namespace App\Filament\Widgets\Default\Ddi;

use App\Enums\Residence\SubType;
use App\Models\Residence;
use App\Models\ResidenceStatsView;
use App\Policies\DistrictDashboardPolicy;
use App\Support\DdiWidgetSupport;
use App\Support\WidgetColorPalette;
use App\Traits\DdiFiltersProvinceAndDistrict;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MarketShareProgressWidget extends ChartWidget
{
    use DdiFiltersProvinceAndDistrict;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '260px';

    public ?string $filter = 'monthly';

    private const ACTIVE_STATUSES = [3, 4, 5];

    private const PERIODS_COUNT = ['daily' => 30, 'weekly' => 12, 'monthly' => 12];

    public static function canView(): bool
    {
        return Gate::allows(DistrictDashboardPolicy::VIEW_ABILITY);
    }

    public function getHeading(): string
    {
        $label = match ($this->filter) {
            'daily' => __('app.daily'),
            'weekly' => __('app.weekly'),
            default => __('app.monthly'),
        };

        return __('app.active_market_share_trend', ['period' => $label]);
    }

    protected function getData(): array
    {
        $cacheKey = DdiWidgetSupport::cacheKey('market_share', [
            $this->filter, $this->provinceIds, $this->districtIds,
        ]);

        return Cache::remember($cacheKey, 120, function () {
            $periods = $this->getTimeRangeConfig();
            $residencesBySubType = $this->getResidencesBySubType();
            $auditData = $this->getAuditDataForAllPeriods($residencesBySubType, $periods);
            $allCounts = $this->calculateAllCounts($residencesBySubType, $periods, $auditData);
            $datasets = $this->buildDatasets($allCounts, $periods);

            return [
                'datasets' => $datasets,
                'labels' => array_map(fn ($period) => $this->formatPeriodLabel($period), $periods),
            ];
        });
    }

    protected function buildDatasets(array $allCounts, array $periods): array
    {
        $datasets = [];
        $colorMap = $this->getColorMap();

        foreach (SubType::publicOptions() as $subTypeValue => $subTypeLabel) {
            if (! isset($allCounts[$subTypeValue])) {
                continue;
            }

            $data = $this->buildDatasetValues($allCounts[$subTypeValue], $periods);

            if (! $this->hasData($data)) {
                continue;
            }

            $color = $colorMap[$subTypeValue] ?? WidgetColorPalette::neutralHex();
            $datasets[] = [
                'label' => $subTypeLabel,
                'data' => array_column($data, 'y'),
                'borderColor' => $color,
                'backgroundColor' => WidgetColorPalette::vehicleLineFill($color, 0.3),
                'borderWidth' => 2,
                'tension' => 0.4,
                'fill' => true,
                'pointRadius' => 3,
                'pointHoverRadius' => 5,
                'pointBackgroundColor' => $color,
                'pointBorderColor' => WidgetColorPalette::whiteHex(),
                'pointBorderWidth' => 1,
            ];
        }

        return $datasets;
    }

    protected function buildDatasetValues(array $counts, array $periods): array
    {
        $data = [];

        foreach ($periods as $index => $period) {
            $activeCount = $counts[$index] ?? 0;
            $data[] = ['y' => $activeCount];
        }

        return $data;
    }

    protected function hasData(array $data): bool
    {
        $values = array_column($data, 'y');

        return max($values) > 0;
    }

    protected function calculateAllCounts(array $residencesBySubType, array $periods, array $auditData): array
    {
        $allCounts = [];
        $endDates = $this->calculateEndDates($periods);

        foreach ($residencesBySubType as $subType => $residences) {
            foreach ($endDates as $index => $endDate) {
                $allCounts[$subType][$index] = $this->countActiveResidences($residences, $endDate, $auditData);
            }
        }

        return $allCounts;
    }

    protected function calculateEndDates(array $periods): array
    {
        return array_map(fn ($period) => match ($this->filter ?? 'monthly') {
            'daily' => Carbon::parse($period)->endOfDay(),
            'weekly' => $this->parseWeekPeriod($period)->endOfWeek(),
            default => Carbon::parse($period.'-01')->endOfMonth(),
        }, $periods);
    }

    protected function countActiveResidences(array $residences, Carbon $endDate, array $auditData): int
    {
        $count = 0;
        $timestamp = $endDate->timestamp;

        foreach ($residences as $id => $residence) {
            $status = $this->getStatusAtDate($id, $residence, $timestamp, $auditData);
            if (in_array($status, self::ACTIVE_STATUSES, true)) {
                $count++;
            }
        }

        return $count;
    }

    protected function getStatusAtDate(int $id, array $residence, int $timestamp, array $auditData): ?int
    {
        if (isset($auditData[$id])) {
            foreach ($auditData[$id] as $audit) {
                if ($audit['created_at']->timestamp <= $timestamp) {
                    return $audit['status'];
                }
            }
        }

        return $residence['created_at']->timestamp <= $timestamp
            ? $residence['residence_activation_status_id']
            : null;
    }

    protected function getResidencesBySubType(): array
    {
        // ->toBase() avoids hydrating models for what can be thousands of rows.
        $rows = ResidenceStatsView::query()
            ->publicMoobans()
            ->inProvinces($this->provinceIds)
            ->inDistricts($this->districtIds)
            ->toBase()
            ->select(['residence_id as id', 'sub_type', 'created_at', 'residence_activation_status_id'])
            ->get();

        return $rows
            ->groupBy('sub_type')
            ->map(fn ($items) => $items->mapWithKeys(fn ($item) => [
                $item->id => [
                    'created_at' => Carbon::parse($item->created_at),
                    'residence_activation_status_id' => $item->residence_activation_status_id,
                ],
            ])->toArray())
            ->toArray();
    }

    protected function getAuditDataForAllPeriods(array $residencesBySubType, array $periods): array
    {
        $ids = collect($residencesBySubType)->flatMap(fn ($r) => array_keys($r))->unique()->values()->all();

        if (empty($ids)) {
            return [];
        }

        $audits = DB::table('audits')
            ->selectRaw("auditable_id, created_at,
                JSON_UNQUOTE(JSON_EXTRACT(old_values, '$.residence_activation_status_id')) as old_status,
                JSON_UNQUOTE(JSON_EXTRACT(new_values, '$.residence_activation_status_id')) as new_status")
            ->whereIn('auditable_id', $ids)
            ->where('auditable_type', Residence::class)
            ->where('created_at', '<=', $this->getMaxDate($periods))
            ->where(function ($query) {
                $query->whereJsonContainsKey('old_values->residence_activation_status_id')
                    ->orWhereJsonContainsKey('new_values->residence_activation_status_id');
            })
            ->orderBy('auditable_id')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->groupAudits($audits);
    }

    protected function getMaxDate(array $periods): Carbon
    {
        $last = end($periods);

        return match ($this->filter ?? 'monthly') {
            'daily' => Carbon::parse($last)->endOfDay(),
            'weekly' => $this->parseWeekPeriod($last)->endOfWeek(),
            default => Carbon::parse($last.'-01')->endOfMonth(),
        };
    }

    protected function groupAudits(object $audits): array
    {
        $grouped = [];

        foreach ($audits as $audit) {
            $oldStatus = isset($audit->old_status) && $audit->old_status !== null
                ? (int) $audit->old_status
                : null;
            $newStatus = isset($audit->new_status) && $audit->new_status !== null
                ? (int) $audit->new_status
                : null;

            if ($oldStatus && $newStatus && $oldStatus !== $newStatus) {
                $grouped[$audit->auditable_id][] = [
                    'created_at' => Carbon::parse($audit->created_at),
                    'status' => $newStatus,
                ];
            }
        }

        return $grouped;
    }

    protected function parseWeekPeriod(string $period): Carbon
    {
        [$year, $week] = explode('-W', $period);
        $date = Carbon::create($year, 1, 1)->startOfWeek();

        if ($date->year < $year) {
            $date->addWeek();
        }

        return $date->addWeeks((int) $week - 1);
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'backgroundColor' => 'rgba(0, 0, 0, 0.9)',
                    'padding' => 12,
                    'titleColor' => WidgetColorPalette::whiteHex(),
                    'bodyColor' => WidgetColorPalette::whiteHex(),
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                    'title' => [
                        'display' => true,
                        'text' => __('app.active_count'),
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => $this->getTimeRangeLabel(),
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
            // Allow the chart to shrink to the widget's `maxHeight`.
            'maintainAspectRatio' => false,
            'responsive' => true,
        ];
    }

    protected function getFilters(): ?array
    {
        return [
            'daily' => __('app.daily'),
            'weekly' => __('app.weekly'),
            'monthly' => __('app.monthly'),
        ];
    }

    protected function getTimeRangeConfig(): array
    {
        $filter = $this->filter ?? 'monthly';
        $count = self::PERIODS_COUNT[$filter] ?? 12;

        return match ($filter) {
            'daily' => $this->generatePeriods($count, 'days'),
            'weekly' => $this->generatePeriods($count, 'weeks'),
            default => $this->generatePeriods($count, 'months'),
        };
    }

    protected function generatePeriods(int $count, string $type): array
    {
        return collect(range($count - 1, 0))->map(fn ($i) => match ($type) {
            'days' => Carbon::now()->subDays($i)->format('Y-m-d'),
            'weeks' => Carbon::now()->subWeeks($i)->format('Y').'-W'.str_pad(Carbon::now()->subWeeks($i)->format('W'), 2, '0', STR_PAD_LEFT),
            'months' => Carbon::now()->subMonths($i)->format('Y-m'),
        })->toArray();
    }

    protected function formatPeriodLabel(string $period): string
    {
        return match ($this->filter ?? 'monthly') {
            'daily' => Carbon::parse($period)->format('M d'),
            'weekly' => 'Week '.substr($period, -2),
            default => Carbon::parse($period.'-01')->format('M Y'),
        };
    }

    protected function getTimeRangeLabel(): string
    {
        return match ($this->filter ?? 'monthly') {
            'daily' => __('app.date'),
            'weekly' => __('app.week'),
            default => __('app.month'),
        };
    }

    protected function getColorMap(): array
    {
        return WidgetColorPalette::ddiSubTypeColors();
    }
}
