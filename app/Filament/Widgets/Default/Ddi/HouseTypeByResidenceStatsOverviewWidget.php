<?php

namespace App\Filament\Widgets\Default\Ddi;

use App\Enums\Unit\HouseType;
use App\Models\Erp\ThailandDistrict;
use App\Models\Erp\ThailandSubDistrict;
use App\Policies\DistrictDashboardPolicy;
use App\Services\UnitWidgetDataService;
use App\Support\QueryGuardSupport;
use App\Support\WidgetColorPalette;
use App\Traits\DdiFiltersProvinceAndDistrict;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;

class HouseTypeByResidenceStatsOverviewWidget extends BaseWidget
{
    use DdiFiltersProvinceAndDistrict;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return Gate::allows(DistrictDashboardPolicy::VIEW_ABILITY);
    }

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getCards(): array
    {
        $subdistrictIds = $this->resolveSubdistrictIdsFromFilters();

        $data = UnitWidgetDataService::getBySubdistrictIds($subdistrictIds);

        $countsByHouseTypeAndSubType = $data['house_type_sub_type'];
        $countsBySubType = $data['sub_type_counts'];
        $notSetCount = $data['house_type_null_count'];

        $cards = collect(HouseType::cases())
            ->map(function (HouseType $houseType) use ($countsByHouseTypeAndSubType, $countsBySubType) {
                $subType = $houseType->subType();
                $count = (int) ($countsByHouseTypeAndSubType[$houseType->value][$subType->value] ?? 0);
                $groupTotal = (int) ($countsBySubType[$subType->value] ?? 0);
                $percentage = $groupTotal > 0 ? ($count / $groupTotal * 100) : 0;
                $percentageFormatted = formatPercentageForStatOverview($percentage);
                $color = WidgetColorPalette::unitHouseTypeHex($houseType->value);

                return Stat::make(
                    new HtmlString("<span style=\"color:{$color};font-weight:bold;\">{$houseType->label()}</span>"),
                    new HtmlString("
                        <div style=\"display: flex; justify-content: space-between; align-items: center;\">
                            <span style=\"color:{$color};font-weight:bold;\">{$count}</span>
                            <span style=\"text-align: right;font-size: 0.75em;\">({$percentageFormatted}%)</span>
                        </div>
                    ")
                )->description("of {$groupTotal} in {$subType->getLabel()}");
            })
            ->values();

        $notSetColor = WidgetColorPalette::unitHouseTypeHex('null');
        $cards->push(
            Stat::make(
                new HtmlString('<span style="color:'.$notSetColor.';font-weight:bold;">Not Set</span>'),
                new HtmlString("
                    <div style=\"display: flex; justify-content: space-between; align-items: center;\">
                        <span style=\"color:{$notSetColor};font-weight:bold;\">{$notSetCount}</span>
                    </div>
                ")
            )->description('Units without house type')
        );

        return $cards->toArray();
    }

    /** DDI filters use ERP province/district IDs; residences store subdistrict_id. */
    private function resolveSubdistrictIdsFromFilters(): ?array
    {
        if (empty($this->provinceIds) && empty($this->districtIds)) {
            return null;
        }

        $districtIds = array_map('intval', (array) $this->districtIds);

        if (! empty($this->provinceIds)) {
            $provinceDistrictIds = ThailandDistrict::query()
                ->whereIn('province_id', array_map('intval', (array) $this->provinceIds), 'and', false)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (empty($districtIds)) {
                $districtIds = $provinceDistrictIds;
            } else {
                $districtIds = array_values(array_intersect($districtIds, $provinceDistrictIds));
            }
        }

        if (empty($districtIds)) {
            return [];
        }

        return QueryGuardSupport::whereInOrDenyAll(
            ThailandSubDistrict::query(),
            'district_id',
            $districtIds,
        )
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
