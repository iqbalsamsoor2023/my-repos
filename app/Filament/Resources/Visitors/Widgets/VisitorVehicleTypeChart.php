<?php

namespace App\Filament\Resources\Visitors\Widgets;

use App\Enums\User\RoleType;
use App\Enums\Visitor\VehicleType;
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

class VisitorVehicleTypeChart extends ChartWidget
{
    use HasDateFilter, InteractsWithPageTable, ResolvesVmsResidence;

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 2, 'xl' => 2];

    protected ?string $maxHeight = '280px';

    protected ?string $pollingInterval = null;

    public function getHeading(): ?string
    {
        return $this->periodHeading('Vehicle Type Breakdown');
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
        $colors = WidgetColorPalette::visitorVehicleType();
        $labels = array_map(fn (VehicleType $t) => $t->getLabel(), VehicleType::cases());

        if ($this->isPmView() && ! $residenceId) {
            return ChartHelper::emptyDoughnut('Vehicle Type', 'No residence found');
        }

        if ($this->usesFilteredTableQuery()) {
            $data = $this->vehicleCountsFromTableQuery();
        } else {
            [$resolvedFrom, $resolvedUntil] = $this->resolvedDates();
            $data = VmsAnalyticsQueryService::globalStats($resolvedFrom, $resolvedUntil, $residenceId);
        }

        $chartData = [
            (int) ($data['vehicle_car'] ?? 0),
            (int) ($data['vehicle_truck'] ?? 0),
            (int) ($data['vehicle_motorbike'] ?? 0),
            (int) ($data['vehicle_van'] ?? 0),
            (int) ($data['vehicle_taxi'] ?? 0),
            (int) ($data['vehicle_pickup'] ?? 0),
        ];

        if (array_sum($chartData) === 0) {
            return ChartHelper::emptyDoughnut('Vehicle Type', 'No vehicle records in this period');
        }

        return [
            'datasets' => [[
                'label' => 'Vehicle Type',
                'data' => $chartData,
                'backgroundColor' => $colors,
                'borderWidth' => 1,
            ]],
            'labels' => $labels,
        ];
    }

    protected function vehicleCountsFromTableQuery(): array
    {
        $baseQuery = $this->getPageTableQuery()->reorder()->toBase();
        $cacheKey = 'vms:widget:visitor_vehicle_type:'.md5($baseQuery->toSql().json_encode($baseQuery->getBindings()));

        return Cache::remember($cacheKey, 60, function () use ($baseQuery) {
            $row = DB::query()
                ->fromSub($baseQuery, 'visitor_scope')
                ->selectRaw('SUM(CASE WHEN vehicle_type = ? THEN 1 ELSE 0 END) as vehicle_car', [VehicleType::CAR->value])
                ->selectRaw('SUM(CASE WHEN vehicle_type = ? THEN 1 ELSE 0 END) as vehicle_truck', [VehicleType::TRUCK->value])
                ->selectRaw('SUM(CASE WHEN vehicle_type = ? THEN 1 ELSE 0 END) as vehicle_motorbike', [VehicleType::MOTORBIKE->value])
                ->selectRaw('SUM(CASE WHEN vehicle_type = ? THEN 1 ELSE 0 END) as vehicle_van', [VehicleType::VAN->value])
                ->selectRaw('SUM(CASE WHEN vehicle_type = ? THEN 1 ELSE 0 END) as vehicle_taxi', [VehicleType::TAXI->value])
                ->selectRaw('SUM(CASE WHEN vehicle_type = ? THEN 1 ELSE 0 END) as vehicle_pickup', [VehicleType::PICKUP->value])
                ->first();

            return [
                'vehicle_car' => (int) ($row->vehicle_car ?? 0),
                'vehicle_truck' => (int) ($row->vehicle_truck ?? 0),
                'vehicle_motorbike' => (int) ($row->vehicle_motorbike ?? 0),
                'vehicle_van' => (int) ($row->vehicle_van ?? 0),
                'vehicle_taxi' => (int) ($row->vehicle_taxi ?? 0),
                'vehicle_pickup' => (int) ($row->vehicle_pickup ?? 0),
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
