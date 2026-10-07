<?php

namespace App\Filament\Widgets\Default\Ddi;

use App\Enums\Residence\ActivationStatusType;
use App\Enums\Residence\SubType;
use App\Models\ResidenceStatsView;
use App\Models\WidgetAggregate;
use App\Policies\DistrictDashboardPolicy;
use App\Support\DdiWidgetSupport;
use App\Traits\DdiFiltersProvinceAndDistrict;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class PropertyStatusGridCardsWidget extends Widget
{
    use DdiFiltersProvinceAndDistrict;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.default.ddi.custom-grid-cards-widget';

    public static function canView(): bool
    {
        return Gate::allows(DistrictDashboardPolicy::VIEW_ABILITY);
    }

    public function getViewData(): array
    {
        return [
            'cards' => $this->getCards(),
        ];
    }

    public function render(): View
    {
        return view($this->view, $this->getViewData());
    }

    public function getCards(): array
    {
        $gridData = $this->getBatchGridData();
        $countsBySubtypeStatus = $gridData['counts_by_subtype_status'] ?? [];
        $totalsBySubtype = $gridData['totals_by_subtype'] ?? [];
        $grandTotal = array_sum($totalsBySubtype);

        $publicSubTypes = [
            SubType::POOL_VILLA,
            SubType::SINGLE_HOME,
            SubType::TWIN_HOME,
            SubType::TOWN_HOME,
            SubType::HOME_OFFICE,
            SubType::CONDO_HIGH_RISE,
            SubType::CONDO_LOW_RISE,
        ];
        $activationStatuses = $this->activationStatuses();

        $cards = [];

        $cardId = 1;

        foreach ($publicSubTypes as $subType) {
            $subTypeValue = $subType->value;
            $subTypeTotal = (int) ($totalsBySubtype[$subTypeValue] ?? 0);
            $percentage = $grandTotal > 0 ? round(($subTypeTotal / $grandTotal) * 100, 1) : 0;

            $cards[] = [
                'id' => $cardId++,
                'title' => $subType->getLabel(),
                'description' => "Public - {$percentage}% of total",
                'value' => $subTypeTotal,
                'trend' => $percentage >= 10 ? 'up' : 'down',
                'color' => $subType->getHex(),
                'type' => 'property',
            ];

            foreach ($activationStatuses as $status) {
                $count = (int) ($countsBySubtypeStatus[$subTypeValue][$status['id']] ?? 0);
                $statusPercentage = $subTypeTotal > 0 ? round(($count / $subTypeTotal) * 100, 1) : 0;

                $cards[] = [
                    'id' => $cardId++,
                    'title' => $status['status'],
                    'description' => "{$subType->getLabel()} - {$statusPercentage}%",
                    'value' => $count,
                    'trend' => $count > 0 ? 'up' : 'down',
                    'color' => $status['color'],
                    'type' => 'activation_status',
                ];
            }
        }

        return $cards;
    }

    /** Uses the pre-computed global cache when no filter is applied. */
    private function getBatchGridData(): array
    {
        if (empty($this->provinceIds) && empty($this->districtIds)) {
            $cacheKey = 'ddi_grid_card_data';

            $cached = WidgetAggregate::getCached($cacheKey, 300);
            if ($cached) {
                return $cached;
            }
        }

        $filterKey = DdiWidgetSupport::cacheKey('grid_cards', [$this->provinceIds, $this->districtIds]);

        return Cache::remember($filterKey, 60, function () {
            $counts = ResidenceStatsView::query()
                ->publicMoobans()
                ->inProvinces($this->provinceIds)
                ->inDistricts($this->districtIds)
                ->selectRaw('sub_type, residence_activation_status_id, COUNT(*) as cnt')
                ->groupBy('sub_type', 'residence_activation_status_id')
                ->get()
                ->groupBy('sub_type')
                ->map(fn ($items) => $items->pluck('cnt', 'residence_activation_status_id')->toArray())
                ->toArray();

            $totals = collect($counts)
                ->map(static fn (array $statusCounts): int => array_sum(array_map('intval', $statusCounts)))
                ->toArray();

            return [
                'counts_by_subtype_status' => $counts,
                'totals_by_subtype' => $totals,
            ];
        });
    }

    /** @return array<int, array{id: int, status: string, color: string}> */
    private function activationStatuses(): array
    {
        return collect([
            ActivationStatusType::INACTIVE_DEMO,
            ActivationStatusType::INACTIVE_CANCELLED,
            ActivationStatusType::ACTIVE_GT_ONLY,
            ActivationStatusType::ACTIVE_GP_ONLY,
            ActivationStatusType::ACTIVE_ALL,
            ActivationStatusType::ACTIVE_MYMOOBAN_LITE,
        ])->map(fn (ActivationStatusType $status) => [
            'id' => $status->value,
            'status' => $status->getLabel(),
            'color' => $status->getHex(),
        ])->all();
    }
}
