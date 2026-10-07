<?php

namespace App\Filament\Widgets\Default\Ddi;

use App\Enums\Residence\PropertyManagementType;
use App\Models\ResidenceStatsView;
use App\Policies\DistrictDashboardPolicy;
use App\Support\DdiWidgetSupport;
use App\Support\WidgetColorPalette;
use App\Traits\DdiFiltersProvinceAndDistrict;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class PropertyManagementTypeChart extends ChartWidget
{
    use DdiFiltersProvinceAndDistrict;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 4;

    protected ?string $heading = 'Property Management Type Distribution';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return Gate::allows(DistrictDashboardPolicy::VIEW_ABILITY);
    }

    protected function getData(): array
    {
        $cacheKey = DdiWidgetSupport::cacheKey('property_management_stats', [$this->provinceIds, $this->districtIds]);

        $counts = Cache::remember($cacheKey, 60, function () {
            return ResidenceStatsView::query()
                ->publicMoobans()
                ->inProvinces($this->provinceIds)
                ->inDistricts($this->districtIds)
                ->selectRaw('property_management_type, COUNT(*) as total')
                ->groupBy('property_management_type')
                ->pluck('total', 'property_management_type')
                ->toArray();
        });

        $types = PropertyManagementType::cases();

        return [
            'labels' => collect($types)->map(fn (PropertyManagementType $type) => $type->getShortLabel())->toArray(),
            'datasets' => [
                [
                    'label' => 'Management Types',
                    'data' => collect($types)->map(fn (PropertyManagementType $type) => $counts[$type->value] ?? 0)->toArray(),
                    'backgroundColor' => collect($types)->map(fn (PropertyManagementType $type) => $type->getHex())->toArray(),
                    'hoverOffset' => 6,
                    'borderWidth' => 1,
                    'borderColor' => WidgetColorPalette::whiteHex(),
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
