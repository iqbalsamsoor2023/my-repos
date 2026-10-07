<?php

namespace App\Filament\Widgets\Default\Ddi;

use App\Models\Erp\ThailandDistrict;
use App\Models\Erp\ThailandProvince;
use App\Policies\DistrictDashboardPolicy;
use App\Services\CustomerSuccessZoneService;
use App\Support\DdiWidgetSupport;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Grid;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Gate;

class DdiFilterWidget extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?int $sort = 0;

    protected string $view = 'filament.widgets.default.ddi.ddi-filter';

    protected int|string|array $columnSpan = 'full';

    public ?array $customer_success_zone_ids = [];

    public ?array $province_ids = [];

    public ?array $district_ids = [];

    public static function canView(): bool
    {
        return Gate::allows(DistrictDashboardPolicy::VIEW_ABILITY);
    }

    protected function getFormSchema(): array
    {
        $nameField = app()->getLocale() === 'th' ? 'name_in_thai' : 'name_in_english';

        return [
            Grid::make(3)
                ->schema([
                    Select::make('customer_success_zone_ids')
                        ->label('CS Zone')
                        ->placeholder('All CS Zones')
                        ->options(fn (): array => CustomerSuccessZoneService::getOptions())
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $deduped = DdiWidgetSupport::normalizeIds($state);
                            $set('customer_success_zone_ids', $deduped);
                            $set('province_ids', []);
                            $set('district_ids', []);
                            $this->customer_success_zone_ids = $deduped;
                            $this->province_ids = [];
                            $this->district_ids = [];
                            $this->dispatchFilters();
                        }),
                    Select::make('province_ids')
                        ->label(__('app.province'))
                        ->placeholder(__('app.all_provinces'))
                        ->options(function (callable $get) use ($nameField) {
                            $customerSuccessZoneIds = DdiWidgetSupport::normalizeIds($get('customer_success_zone_ids'));
                            $customerSuccessDistrictIds = $this->resolveCustomerSuccessDistrictIds($customerSuccessZoneIds);
                            $query = ThailandProvince::query()->orderBy('name_in_english', 'asc');

                            if (! empty($customerSuccessDistrictIds)) {
                                $provinceIds = ThailandDistrict::query()
                                    ->whereIn('id', $customerSuccessDistrictIds, 'and', false)
                                    ->distinct()
                                    ->pluck('province_id')
                                    ->map(static fn ($provinceId): int => (int) $provinceId)
                                    ->all();

                                if (empty($provinceIds)) {
                                    return [];
                                }

                                $query->whereIn('id', $provinceIds, 'and', false);
                            }

                            return $query->get(['id', 'name_in_english', 'name_in_thai'])->pluck($nameField, 'id')->toArray();
                        })
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('district_ids', []);
                            $deduped = DdiWidgetSupport::normalizeIds($state);
                            $this->province_ids = $deduped;
                            $this->district_ids = [];
                            $this->dispatchFilters();
                        }),

                    Select::make('district_ids')
                        ->label(__('app.district'))
                        ->placeholder(__('app.all_districts'))
                        ->options(function (callable $get) use ($nameField) {
                            $provinceIds = DdiWidgetSupport::normalizeIds($get('province_ids'));
                            $customerSuccessZoneIds = DdiWidgetSupport::normalizeIds($get('customer_success_zone_ids'));
                            $customerSuccessDistrictIds = $this->resolveCustomerSuccessDistrictIds($customerSuccessZoneIds);
                            $query = ThailandDistrict::query()->orderBy('name_in_english', 'asc');

                            if (! empty($provinceIds)) {
                                $query->whereIn('province_id', $provinceIds, 'and', false);
                            }

                            if (! empty($customerSuccessDistrictIds)) {
                                $query->whereIn('id', $customerSuccessDistrictIds, 'and', false);
                            }

                            return $query->get(['id', 'name_in_english', 'name_in_thai'])->pluck($nameField, 'id')->toArray();
                        })
                        ->multiple()
                        ->preload()
                        ->native(false)
                        ->live()
                        ->disabled(fn (callable $get) => empty($get('province_ids')))
                        ->afterStateUpdated(function ($state, callable $set) {
                            $deduped = DdiWidgetSupport::normalizeIds($state);
                            $set('district_ids', $deduped);
                            $this->district_ids = $deduped;
                            $this->dispatchFilters();
                        }),
                ]),
        ];
    }

    protected function dispatchFilters(): void
    {
        $customerSuccessZoneIds = DdiWidgetSupport::normalizeIds($this->customer_success_zone_ids);
        $provinceIds = DdiWidgetSupport::normalizeIds($this->province_ids);
        $selectedDistrictIds = DdiWidgetSupport::normalizeIds($this->district_ids);
        $effectiveDistrictIds = $selectedDistrictIds;

        if (! empty($customerSuccessZoneIds)) {
            $districtIdsInCsZones = $this->resolveCustomerSuccessDistrictIds($customerSuccessZoneIds);

            if (! empty($provinceIds)) {
                $districtIdsInCsZones = ThailandDistrict::query()
                    ->whereIn('id', $districtIdsInCsZones, 'and', false)
                    ->whereIn('province_id', $provinceIds, 'and', false)
                    ->pluck('id')
                    ->map(static fn ($districtId): int => (int) $districtId)
                    ->all();
            }

            if (empty($selectedDistrictIds)) {
                $effectiveDistrictIds = $districtIdsInCsZones;
            } else {
                $effectiveDistrictIds = DdiWidgetSupport::normalizeIds(array_values(array_intersect($selectedDistrictIds, $districtIdsInCsZones)));
            }
        }

        $this->dispatch('ddi-filters-updated', filters: [
            // Keep province_id for backward compatibility with any legacy listeners.
            'customer_success_zone_ids' => $customerSuccessZoneIds,
            'province_id' => $provinceIds[0] ?? null,
            'province_ids' => $provinceIds,
            'district_ids' => $effectiveDistrictIds,
        ]);
    }

    private function resolveCustomerSuccessDistrictIds(array $customerSuccessZoneIds): array
    {
        return CustomerSuccessZoneService::getDistrictIds($customerSuccessZoneIds);
    }
}
