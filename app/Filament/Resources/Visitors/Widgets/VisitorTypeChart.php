<?php

namespace App\Filament\Resources\Visitors\Widgets;

use App\Enums\User\RoleType;
use App\Enums\Visitor\ArrivalType;
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

class VisitorTypeChart extends ChartWidget
{
    use HasDateFilter, InteractsWithPageTable, ResolvesVmsResidence;

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 2, 'xl' => 2];

    protected ?string $maxHeight = '280px';

    protected ?string $pollingInterval = null;

    public function getHeading(): ?string
    {
        return $this->periodHeading('Visitor Type Breakdown');
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
        $colors = WidgetColorPalette::visitorType();
        $labels = [
            ArrivalType::DRIVE_IN->getLabel(),
            ArrivalType::WALK_IN->getLabel(),
            'Prebook',
        ];

        if ($this->isPmView() && ! $residenceId) {
            return ChartHelper::emptyDoughnut('Visitor Type', 'No residence found');
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

        if (array_sum($counts) === 0) {
            return ChartHelper::emptyDoughnut('Visitor Type', 'No data in this period');
        }

        return [
            'datasets' => [[
                'label' => 'Visitor Type',
                'data' => $counts,
                'backgroundColor' => $colors,
                'borderWidth' => 1,
            ]],
            'labels' => $labels,
        ];
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

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getTablePage(): string
    {
        return ListVisitors::class;
    }
}
