<?php

namespace App\Filament\Widgets\Default\Vbs;

use App\Enums\Residence\MoobanType;
use App\Models\ResidenceActivationStatus;
use App\Models\ResidenceStatsView;
use App\Services\CustomerSuccessZoneService;
use Carbon\Carbon;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Widgets\Widget;

class VbsFilterWidget extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null
            && ($user->hasAnyRole(['Super Admin', 'Admin']) ?? false)
            && (request()?->is('*visitor-by-site*') || request()?->routeIs('filament.admin.pages.visitor-by-site'));
    }

    protected static ?int $sort = 0;

    protected string $view = 'filament.widgets.default.vbs.vbs-filter';

    protected int|string|array $columnSpan = 'full';

    public ?array $activation_status_ids = [];

    public ?array $customer_success_zone_ids = [];

    public ?int $province_id = null;

    public ?array $district_ids = [];

    public ?array $residence_ids = [];

    public ?array $mooban_type = [];

    public ?array $sub_type = [];

    public ?string $month = null;

    public function mount(): void
    {
        $this->month = Carbon::now()->format('Y-m');

        $this->form->fill([
            'activation_status_ids' => [],
            'customer_success_zone_ids' => [],
            'province_id' => null,
            'district_ids' => [],
            'residence_ids' => [],
            'mooban_type' => [],
            'sub_type' => [],
            'month' => $this->month,
        ]);
    }

    protected function getFormSchema(): array
    {
        $activationOptions = ResidenceActivationStatus::orderBy('id')
            ->pluck('status', 'id')
            ->toArray();

        return [
            Section::make(__('vbs.filter_group_period'))
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('month')
                            ->label(__('vbs.month'))
                            ->placeholder(__('vbs.current_month'))
                            ->options(function () {
                                $now = Carbon::now();

                                return collect(range(0, 23))
                                    ->mapWithKeys(function ($i) use ($now) {
                                        $month = $now->copy()->subMonths($i);

                                        return [$month->format('Y-m') => $month->format('M Y')];
                                    })
                                    ->toArray();
                            })
                            ->native(false),

                        Select::make('mooban_type')
                            ->label(__('residence.mooban_type'))
                            ->placeholder(__('app.all'))
                            ->options(collect(MoobanType::cases())->mapWithKeys(fn ($type) => [
                                $type->value => $type->getLabel(),
                            ]))
                            ->preload()
                            ->multiple()
                            ->native(false),

                        Select::make('sub_type')
                            ->label(__('residence.house_type'))
                            ->placeholder(__('app.all'))
                            ->multiple()
                            ->options(function (callable $get) {
                                $moobanTypes = (array) $get('mooban_type');

                                if (empty($moobanTypes)) {
                                    return [];
                                }

                                $filteredSubTypes = collect($moobanTypes)
                                    ->map(fn ($moobanType) => MoobanType::tryFrom((int) $moobanType))
                                    ->filter()
                                    ->flatMap(fn ($moobanType) => $moobanType->allowedSubTypes() ?? [])
                                    ->unique();

                                return $filteredSubTypes->mapWithKeys(fn ($subType) => [
                                    $subType->value => $subType->getLabel(),
                                ])->toArray();
                            })
                            ->preload()
                            ->native(false),
                    ]),
                ]),

            Section::make(__('vbs.filter_group_location'))
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('activation_status_ids')
                            ->label(__('vbs.activation_status'))
                            ->placeholder(__('vbs.all_statuses'))
                            ->multiple()
                            ->options($activationOptions)
                            ->searchable()
                            ->preload()
                            ->native(false),

                        Select::make('customer_success_zone_ids')
                            ->label('CS Zone')
                            ->placeholder('All CS Zones')
                            ->multiple()
                            ->options(fn (): array => CustomerSuccessZoneService::getOptions())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->reactive()
                            ->afterStateUpdated(function (callable $set): void {
                                $set('province_id', null);
                                $set('district_ids', []);
                            }),

                        Select::make('province_id')
                            ->label(__('vbs.province'))
                            ->placeholder(__('vbs.all_provinces'))
                            ->options(fn (callable $get): array => CustomerSuccessZoneService::getProvinceOptions(
                                (array) ($get('customer_success_zone_ids') ?? [])
                            ))
                            ->reactive()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->afterStateUpdated(fn (callable $set) => $set('district_ids', [])),

                        Select::make('district_ids')
                            ->label(__('vbs.district'))
                            ->placeholder(__('vbs.all_districts'))
                            ->multiple()
                            ->options(fn (callable $get): array => filled($get('province_id'))
                                ? CustomerSuccessZoneService::getDistrictOptions(
                                    [(int) $get('province_id')],
                                    (array) ($get('customer_success_zone_ids') ?? [])
                                )
                                : []
                            )
                            ->searchable()
                            ->preload()
                            ->disabled(fn (callable $get) => blank($get('province_id')))
                            ->native(false),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('residence_ids')
                            ->label(__('vbs.residence_site'))
                            ->placeholder(__('vbs.all_sites'))
                            ->columnSpanFull()
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->getOptionLabelsUsing(fn (?array $values): array => ResidenceStatsView::whereIn('residence_id', array_map('intval', $values ?? []))
                                ->get(['residence_id', 'name', 'name_th'])
                                ->mapWithKeys(fn ($row) => [(string) $row->residence_id => __('vbs.site_label', ['en' => $row->name, 'th' => $row->name_th])])
                                ->toArray()
                            )
                            ->getSearchResultsUsing(fn (string $search): array => ResidenceStatsView::where(fn ($q) => $q
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('name_th', 'like', "%{$search}%")
                            )
                                ->orderBy('name')
                                ->limit(50)
                                ->get(['residence_id', 'name', 'name_th'])
                                ->mapWithKeys(fn ($row) => [(string) $row->residence_id => __('vbs.site_label', ['en' => $row->name, 'th' => $row->name_th])])
                                ->toArray()
                            ),
                    ]),
                ]),
        ];
    }

    protected function dispatchFilters(): void
    {
        $customerSuccessZoneIds = $this->normalizeIds($this->customer_success_zone_ids ?? []);
        $selectedDistrictIds = $this->normalizeIds($this->district_ids ?? []);
        $effectiveDistrictIds = $selectedDistrictIds;

        if (! empty($customerSuccessZoneIds)) {
            $districtIdsInZone = filled($this->province_id)
                ? array_map('intval', array_keys(CustomerSuccessZoneService::getDistrictOptions([(int) $this->province_id], $customerSuccessZoneIds)))
                : CustomerSuccessZoneService::getDistrictIds($customerSuccessZoneIds);

            if (empty($selectedDistrictIds)) {
                $effectiveDistrictIds = $districtIdsInZone;
            } else {
                $effectiveDistrictIds = array_values(array_intersect($selectedDistrictIds, $districtIdsInZone));
            }
        }

        $this->dispatch('vbs-filters-updated', filters: [
            'activation_status_ids' => $this->activation_status_ids ?? [],
            'customer_success_zone_ids' => $customerSuccessZoneIds,
            'province_id' => $this->province_id,
            'district_ids' => $effectiveDistrictIds,
            'residence_ids' => $this->residence_ids ?? [],
            'mooban_type' => $this->mooban_type ?? [],
            'sub_type' => $this->sub_type ?? [],
            'month' => $this->month,
        ]);
    }

    public function applyFiltersFromForm(): void
    {
        $state = $this->form->getState();

        $this->activation_status_ids = array_values(array_filter(array_map('intval', array_unique($state['activation_status_ids'] ?? []))));
        $this->customer_success_zone_ids = $this->normalizeIds($state['customer_success_zone_ids'] ?? []);
        $this->province_id = filled($state['province_id'] ?? null) ? (int) $state['province_id'] : null;
        $this->district_ids = $this->normalizeIds($state['district_ids'] ?? []);
        $this->residence_ids = array_values(array_filter(array_map('intval', array_unique($state['residence_ids'] ?? []))));

        $selectedMooban = array_values(array_filter(array_map('intval', array_unique($state['mooban_type'] ?? []))));
        $this->mooban_type = $selectedMooban;

        // Normalize sub_type to only allowed values for selected mooban types
        $allowedSubTypeValues = collect($selectedMooban)
            ->map(fn ($mt) => MoobanType::tryFrom((int) $mt))
            ->filter()
            ->flatMap(fn ($mt) => $mt->allowedSubTypes() ?? [])
            ->map(fn ($st) => $st->value)
            ->unique()
            ->values()
            ->toArray();

        $rawSubTypes = array_values(array_filter(array_map('intval', array_unique($state['sub_type'] ?? []))));
        $this->sub_type = array_values(array_intersect($rawSubTypes, $allowedSubTypeValues));

        $this->month = $state['month'] ?? $this->month;

        $this->dispatchFilters();
    }

    public function resetFilters(): void
    {
        $this->form->fill([
            'activation_status_ids' => [],
            'customer_success_zone_ids' => [],
            'province_id' => null,
            'district_ids' => [],
            'residence_ids' => [],
            'mooban_type' => [],
            'sub_type' => [],
            'month' => Carbon::now()->format('Y-m'),
        ]);

        // Reset internal state as well
        $this->activation_status_ids = [];
        $this->customer_success_zone_ids = [];
        $this->province_id = null;
        $this->district_ids = [];
        $this->residence_ids = [];
        $this->mooban_type = [];
        $this->sub_type = [];
        $this->month = Carbon::now()->format('Y-m');

        $this->dispatchFilters();
    }

    /**
     * @param  array<int|string|null>  $values
     * @return array<int>
     */
    private function normalizeIds(array $values): array
    {
        return array_values(array_filter(array_map('intval', array_unique($values))));
    }
}
