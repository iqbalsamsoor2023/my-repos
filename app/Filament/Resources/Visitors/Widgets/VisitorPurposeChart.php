<?php

namespace App\Filament\Resources\Visitors\Widgets;

use App\Enums\User\RoleType;
use App\Filament\Resources\Visitors\Pages\ListVisitors;
use App\Filament\Resources\Visitors\Widgets\Concerns\HasDateFilter;
use App\Filament\Resources\Visitors\Widgets\Concerns\ResolvesVmsResidence;
use App\Helpers\ChartHelper;
use App\Services\VmsAnalyticsQueryService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VisitorPurposeChart extends ChartWidget
{
    use HasDateFilter, InteractsWithPageTable, ResolvesVmsResidence;

    private const MAX_SEGMENTS = 7;

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 2, 'xl' => 2];

    protected ?string $maxHeight = '280px';

    protected ?string $pollingInterval = null;

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

    protected function getData(): array
    {
        $residenceId = $this->pmResidenceId();

        if ($this->isPmView() && ! $residenceId) {
            return ChartHelper::emptyDoughnut('Purpose of Visit', 'No residence found');
        }

        if ($this->usesFilteredTableQuery()) {
            $purposeData = $this->purposeFromTableQuery();
        } else {
            [$resolvedFrom, $resolvedUntil] = $this->resolvedDates();
            $purposeData = VmsAnalyticsQueryService::purposeBreakdown($resolvedFrom, $resolvedUntil, $residenceId);
        }

        $topPurposes = array_slice($purposeData, 0, self::MAX_SEGMENTS, true);
        $otherCount = array_sum(array_slice($purposeData, self::MAX_SEGMENTS));

        $labels = array_keys($topPurposes);
        $counts = array_values($topPurposes);

        if ($otherCount > 0) {
            $labels[] = 'Others';
            $counts[] = $otherCount;
        }

        if (empty($counts) || array_sum($counts) === 0) {
            return ChartHelper::emptyDoughnut('Purpose of Visit', 'No data in this period');
        }

        $colors = WidgetColorPalette::visitorPurpose();

        return [
            'datasets' => [[
                'label' => 'Purpose',
                'data' => $counts,
                'backgroundColor' => array_slice($colors, 0, count($counts)),
                'borderWidth' => 1,
            ]],
            'labels' => $labels,
        ];
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

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getTablePage(): string
    {
        return ListVisitors::class;
    }
}
