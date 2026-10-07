<?php

namespace App\Filament\Widgets\Default\Ddi;

use App\Enums\Residence\ActivationStatusType;
use App\Enums\Residence\PropertyManagementType;
use App\Enums\Residence\SubType;
use App\Models\BpoSoftwareSupplier;
use App\Models\Residence;
use App\Models\ResidenceActivationStatus;
use App\Policies\DistrictDashboardPolicy;
use App\Services\DistrictDataSummaryQueryService;
use App\Support\BpoSoftwareSupplierFilterHelper;
use App\Support\WidgetColorPalette;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;

class DistrictDataSummaryWidget extends BaseWidget
{
    private const MONTH_OPTIONS = [
        '01' => 'January',
        '02' => 'February',
        '03' => 'March',
        '04' => 'April',
        '05' => 'May',
        '06' => 'June',
        '07' => 'July',
        '08' => 'August',
        '09' => 'September',
        '10' => 'October',
        '11' => 'November',
        '12' => 'December',
    ];

    use InteractsWithTable {
        applyTableFilters as protected filamentApplyTableFilters;
    }

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public ?array $provinceIds = [];

    public ?array $districtIds = [];

    public static function canView(): bool
    {
        return Gate::allows(DistrictDashboardPolicy::VIEW_ABILITY);
    }

    #[On('ddi-filters-updated')]
    public function applyFilters(array $filters): void
    {
        $this->provinceIds = (array) ($filters['province_ids'] ?? []);
        $this->districtIds = $filters['district_ids'] ?? [];
        $this->resetTable();
    }

    public function applyTableFilters(): void
    {
        $this->filamentApplyTableFilters();

        $this->dispatchResidentMarketShareFilters();
    }

    protected function dispatchResidentMarketShareFilters(): void
    {
        $this->dispatch('ddi-resident-market-share-filters-updated', filters: [
            'sub_types' => $this->normalizeMultiSelectFilterValues($this->tableFilters['sub_types'] ?? []),
            'activation_status_ids' => $this->normalizeMultiSelectFilterValues($this->tableFilters['activation_status_ids'] ?? []),
            'property_management_types' => $this->normalizeMultiSelectFilterValues($this->tableFilters['property_management_types'] ?? []),
        ]);
    }

    protected function normalizeMultiSelectFilterValues(array $filter): array
    {
        $values = $filter['values'] ?? $filter['value'] ?? [];

        if (! is_array($values)) {
            $values = [$values];
        }

        return array_values(array_filter($values, static fn ($value) => $value !== null && $value !== ''));
    }

    public function table(Table $table): Table
    {
        $serviceDurationOptions = $this->serviceDurationOptions();
        $activationStatusOptions = ResidenceActivationStatus::query()->pluck('status', 'id')->toArray();
        $supplierOptions = BpoSoftwareSupplier::query()->orderBy('name', 'asc')->pluck('name', 'id')->toArray();

        return $table
            ->heading(__('app.data_summary'))
            ->query($this->getTableQuery())
            ->columns($this->buildTableColumns())
            ->filters($this->buildTableFilters(
                $activationStatusOptions,
                $serviceDurationOptions,
                $supplierOptions,
            ), layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label(__('app.view'))
                        ->icon('heroicon-o-eye')
                        ->url(
                            fn ($record) => route('filament.admin.resources.residences.edit', ['record' => $record->id])
                        )
                        ->openUrlInNewTab(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->defaultSort('residences.updated_at', 'desc')
            ->paginationMode(PaginationMode::Default)
            ->paginationPageOptions([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }

    private function monthNumberFromName(string $search): ?string
    {
        $monthMap = collect(self::MONTH_OPTIONS)
            ->mapWithKeys(fn (string $name, string $number) => [strtolower($name) => $number])
            ->all();

        return $monthMap[strtolower(trim($search))] ?? null;
    }

    private function serviceDurationOptions(): array
    {
        $options = [];

        for ($y = 1; $y <= 5; $y++) {
            for ($m = 1; $m <= 12; $m++) {
                $value = sprintf('%02d%02d', $y, $m);
                $options[$value] = $value;
            }
        }

        return $options;
    }

    protected function getTableQuery(): Builder
    {
        return DistrictDataSummaryQueryService::build(
            $this->provinceIds ?? [],
            $this->districtIds ?? [],
        );
    }

    private function buildTableColumns(): array
    {
        return [
            TextColumn::make('activation_status_name')
                ->label(__('app.activation_status'))
                ->badge()
                ->color(fn ($state, $record) => ActivationStatusType::tryFrom((int) ($record->residence_activation_status_id ?? 0))?->getColor()
                    ?? WidgetColorPalette::statColorFromHex(WidgetColorPalette::neutralHex()))
                ->searchable(query: fn ($query, $search) => $query->where('ras.status', 'like', "%{$search}%"))
                ->toggleable()
                ->sortable(query: fn ($query, $direction) => $query->orderBy('ras.status', $direction)),
            TextColumn::make('district_name')
                ->label(__('app.district'))
                ->description(fn ($record): string => $record->district_name_th ?? '-')
                ->toggleable()
                ->sortable(query: fn ($query, $direction) => $query->orderBy('td.name_in_english', $direction)),
            TextColumn::make('name')
                ->label(__('app.mooban_name'))
                ->description(fn ($record) => $record->name_th ?? '-')
                ->searchable(query: function ($query, $search) {
                    $query->where('residences.name', 'like', "%{$search}%")
                        ->orWhere('residences.name_th', 'like', "%{$search}%");
                })
                ->sortable(),
            TextColumn::make('pm_user_name')
                ->label(__('residence.mooban_id'))
                ->copyable()
                ->toggleable(),
            TextColumn::make('sub_type')
                ->label(__('app.house_type'))
                ->formatStateUsing(fn ($state) => SubType::tryFrom($state)?->getLabel() ?? '-')
                ->sortable(),
            TextColumn::make('units_count')
                ->label(__('app.total_unit'))
                ->numeric()
                ->toggleable()
                ->sortable(),
            TextColumn::make('distinct_user_count')
                ->label(__('app.total_resident'))
                ->numeric()
                ->toggleable()
                ->sortable(),
            TextColumn::make('sign_up_percentage')
                ->label(__('app.sign_up_rate'))
                ->badge()
                ->color(fn ($state, $record) => WidgetColorPalette::statColorFromHex(match (true) {
                    in_array($record->residence_activation_status_id, [
                        ActivationStatusType::INACTIVE_DEMO->value,
                        ActivationStatusType::ACTIVE_GT_ONLY->value,
                        ActivationStatusType::ACTIVE_DEMO->value,
                    ]) => WidgetColorPalette::neutralHex(),
                    (float) $state === 0.0 => WidgetColorPalette::neutralHex(),
                    (float) $state > 0 && (float) $state <= 25 => WidgetColorPalette::ddiActivationStatusHex(2),
                    (float) $state > 25 && (float) $state <= 50 => WidgetColorPalette::amberHex(),
                    (float) $state > 50 && (float) $state <= 75 => WidgetColorPalette::blueHex(),
                    (float) $state > 75 && (float) $state <= 100 => WidgetColorPalette::emeraldHex(),
                    default => WidgetColorPalette::neutralHex(),
                }))
                ->getStateUsing(fn ($record) => round((float) $record->sign_up_percentage, 2).'%'),
            TextColumn::make('property_management_type')
                ->label(__('app.pm_type'))
                ->badge()
                ->formatStateUsing(fn (?string $state): string => $state
                    ? PropertyManagementType::tryFrom((int) $state)?->getShortLabel() ?? '-'
                    : '-')
                ->color(fn (?string $state) => PropertyManagementType::tryFrom((int) $state)?->getColor() ?? WidgetColorPalette::statColorFromHex(WidgetColorPalette::neutralHex()))
                ->sortable(),
            TextColumn::make('pm_company_name')
                ->label(__('app.pm_company_name'))
                ->searchable(
                    query: fn ($query, $search) => $query->where('rsv.pm_company_name', 'like', "%{$search}%")
                )
                ->sortable(
                    query: fn ($query, $direction) => $query->orderBy('rsv.pm_company_name', $direction)
                ),
            TextColumn::make('juristic_details.name')
                ->label(__('app.juristic_name'))
                ->getStateUsing(fn ($record): string => is_array($record->juristic_details) ? ($record->juristic_details['name'] ?? '-') : '-')
                ->searchable(query: fn ($query, $search) => $query->where('residences.juristic_details->name', 'like', "%{$search}%")),
            TextColumn::make('juristic_details.phone_no')
                ->label(__('app.juristic_phone_no'))
                ->getStateUsing(fn ($record): string => is_array($record->juristic_details) ? ($record->juristic_details['phone_no'] ?? '-') : '-')
                ->searchable(query: fn ($query, $search) => $query->where('residences.juristic_details->phone_no', 'like', "%{$search}%"))
                ->toggleable(),
            TextColumn::make('security_guard_count')
                ->label(__('residence.number_of_security_guards'))
                ->numeric()
                ->toggleable()
                ->sortable(),
            TextColumn::make('sg_expiry_date')
                ->label(__('residence.sg_expiry_date'))
                ->getStateUsing(fn (Model $record) => $record->sg_expiry_date ? Carbon::parse($record->sg_expiry_date)->format('d-M-y') : 'N/A')
                ->toggleable(),
            TextColumn::make('contracts')
                ->label(__('app.contracts'))
                ->alignCenter()
                ->badge()
                ->color(function (Model $record): string {
                    if (! $record->subscription_end_date) {
                        return 'gray';
                    }

                    $diffInDays = (int) Carbon::today()->diffInDays(Carbon::parse($record->subscription_end_date)->endOfDay(), false);

                    return match (true) {
                        $diffInDays < 0 => 'danger',
                        $diffInDays <= 30 => 'warning',
                        default => 'success',
                    };
                })
                ->getStateUsing(fn (Model $record) => $record->subscription_end_date ? Carbon::parse($record->subscription_end_date)->format('d-M-y') : '-')
                ->description(function (Model $record): string {
                    if (! $record->subscription_end_date) {
                        return '-';
                    }

                    $diffInDays = (int) Carbon::today()->diffInDays(Carbon::parse($record->subscription_end_date)->endOfDay(), false);
                    $absDays = abs($diffInDays);

                    return match (true) {
                        $diffInDays < 0 => __('app.expired_days_ago', ['days' => $absDays]),
                        $diffInDays === 0 => __('app.days_left', ['days' => 0]),
                        $diffInDays === 1 => __('app.days_left', ['days' => 1]),
                        default => __('app.days_left', ['days' => $diffInDays]),
                    };
                })
                ->toggleable(),
            TextColumn::make('service_duration_latest')
                ->label(__('app.service_duration'))
                ->getStateUsing(function ($record): string {
                    if (in_array($record->residence_activation_status_id, [1, 2], true)) {
                        return '-';
                    }

                    return $record->service_duration_latest ?? '-';
                })
                ->toggleable()
                ->sortable(),
            TextColumn::make('bpo_software_suppliers.month')
                ->label('AGM Month')
                ->getStateUsing(function (Model $record): string {
                    if (! isset($record->bpo_software_suppliers['date'])) {
                        return '-';
                    }

                    return Carbon::parse($record->bpo_software_suppliers['date'])->format('F');
                })
                ->toggleable()
                ->searchable(query: function ($query, $search) {
                    $month = $this->monthNumberFromName($search);

                    if ($month !== null) {
                        $query->whereMonth(
                            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(residences.bpo_software_suppliers, '$.date'))"),
                            (int) $month
                        );
                    }
                }),
            TextColumn::make('bpo_software_suppliers.date')
                ->label('AGM Date')
                ->toggleable(),
        ];
    }

    private function buildTableFilters(array $activationStatusOptions, array $serviceDurationOptions, array $supplierOptions): array
    {
        return [
            SelectFilter::make('activation_status_ids')
                ->label(__('app.activation_status'))
                ->multiple()
                ->options($activationStatusOptions)
                ->query(function ($query, array $data) {
                    $values = $this->normalizeMultiSelectFilterValues($data);

                    if (! empty($values)) {
                        return $query->whereIn('residences.residence_activation_status_id', $values);
                    }

                    return $query;
                })
                ->indicateUsing(function (array $data) use ($activationStatusOptions): ?string {
                    $values = $this->normalizeMultiSelectFilterValues($data);

                    if (empty($values)) {
                        return null;
                    }

                    $statuses = collect($values)
                        ->map(fn ($value) => $activationStatusOptions[$value] ?? null)
                        ->filter();

                    return __('app.activation_status').': '.$statuses->join(', ');
                })
                ->preload()
                ->searchable(),
            SelectFilter::make('sub_types')
                ->label(__('app.house_type'))
                ->multiple()
                ->options(SubType::publicOptions())
                ->query(function ($query, array $data) {
                    $values = $this->normalizeMultiSelectFilterValues($data);

                    if (! empty($values)) {
                        return $query->whereIn('residences.sub_type', $values);
                    }

                    return $query;
                })
                ->indicateUsing(function (array $data): ?string {
                    $values = $this->normalizeMultiSelectFilterValues($data);

                    if (empty($values)) {
                        return null;
                    }

                    $types = collect($values)->map(fn ($value) => SubType::tryFrom((int) $value)?->getLabel())->filter();

                    return __('app.house_type').': '.$types->join(', ');
                }),
            Filter::make('service_duration_value')
                ->label(__('app.service_year_month'))
                ->schema([
                    Select::make('value')
                        ->label(__('app.service_year_month'))
                        ->options($serviceDurationOptions)
                        ->searchable()
                        ->placeholder('0101'),
                ])
                ->query(function ($query, array $data) {
                    $value = $data['value'] ?? null;

                    if (! $value) {
                        return;
                    }

                    $query->where('rsv.service_duration_latest', $value);
                })
                ->indicateUsing(function (array $data): ?string {
                    $value = $data['value'] ?? null;

                    return $value ? __('app.service_year_month')." {$value}" : null;
                }),
            SelectFilter::make('agm_month')
                ->label('AGM Month')
                ->options(self::MONTH_OPTIONS)
                ->searchable()
                ->query(function ($query, array $data) {
                    if (empty($data['value'])) {
                        return;
                    }

                    $query->whereMonth(
                        DB::raw("JSON_UNQUOTE(JSON_EXTRACT(residences.bpo_software_suppliers, '$.date'))"),
                        (int) $data['value']
                    );
                }),
            SelectFilter::make('sg_expiry_month')
                ->label(__('residence.sg_expiry_date'))
                ->options(self::MONTH_OPTIONS)
                ->searchable()
                ->query(function ($query, array $data) {
                    if (empty($data['value'])) {
                        return;
                    }

                    $month = (int) $data['value'];

                    $query->whereExists(function ($q) use ($month) {
                        $q->selectRaw('1')
                            ->from('subscription_expires as se')
                            ->whereColumn('se.residence_id', 'residences.id')
                            ->where('se.type', 'Sgoc')
                            ->whereMonth('se.expiry_date', $month);
                    });
                })
                ->indicateUsing(function (array $data): ?string {
                    $value = $data['value'] ?? null;

                    return $value ? __('residence.sg_expiry_date').': '.(self::MONTH_OPTIONS[$value] ?? $value) : null;
                }),
            SelectFilter::make('security_guard_count')
                ->label(__('residence.number_of_security_guards'))
                ->multiple()
                ->options(fn (): array => Residence::query()
                    ->whereNotNull('security_guard_count', 'and')
                    ->select('security_guard_count')
                    ->distinct()
                    ->orderBy('security_guard_count', 'asc')
                    ->pluck('security_guard_count', 'security_guard_count')
                    ->mapWithKeys(fn ($count) => [(string) $count => (string) $count])
                    ->toArray())
                ->query(function ($query, array $data) {
                    $values = $this->normalizeMultiSelectFilterValues($data);

                    if (empty($values)) {
                        return $query;
                    }

                    return $query->whereIn('residences.security_guard_count', array_map('intval', $values));
                })
                ->indicateUsing(function (array $data): ?string {
                    $values = $this->normalizeMultiSelectFilterValues($data);

                    return empty($values)
                        ? null
                        : __('residence.number_of_security_guards').': '.collect($values)->join(', ');
                }),
            $this->makeSoftwareSupplierFilter('software_vms_ids', __('residence.bpo_software_vms_supplier'), 'vms', $supplierOptions),
            $this->makeSoftwareSupplierFilter('software_accounting_ids', __('residence.bpo_software_accounting_supplier'), 'accounting', $supplierOptions),
            $this->makeSoftwareSupplierFilter('software_apps_user_ids', __('residence.bpo_apps_user_supplier'), 'apps_user', $supplierOptions),
            SelectFilter::make('property_management_types')
                ->label(__('app.pm_type'))
                ->options(PropertyManagementType::options())
                ->multiple()
                ->query(function ($query, array $data) {
                    $values = $this->normalizeMultiSelectFilterValues($data);

                    if (! empty($values)) {
                        $query->whereIn('residences.property_management_type', $values);
                    }

                    return $query;
                })
                ->indicateUsing(function (array $data): ?string {
                    $values = $this->normalizeMultiSelectFilterValues($data);

                    if (empty($values)) {
                        return null;
                    }

                    $names = collect($values)
                        ->map(fn ($value) => PropertyManagementType::options()[$value] ?? $value)
                        ->filter();

                    return $names->isEmpty() ? null : __('app.pm_type').': '.$names->join(', ');
                }),
        ];
    }

    private function makeSoftwareSupplierFilter(string $name, string $label, string $jsonKey, array $supplierOptions): SelectFilter
    {
        return SelectFilter::make($name)
            ->label($label)
            ->multiple()
            ->options($supplierOptions)
            ->query(function ($query, array $data) use ($jsonKey) {
                $values = $this->normalizeMultiSelectFilterValues($data);

                if (empty($values)) {
                    return;
                }

                BpoSoftwareSupplierFilterHelper::applyJsonFilter($query, $jsonKey, $values);
            })
            ->indicateUsing(function (array $data) use ($label, $supplierOptions): ?string {
                $values = $this->normalizeMultiSelectFilterValues($data);

                if (empty($values)) {
                    return null;
                }

                $names = collect($values)
                    ->map(fn ($value) => $supplierOptions[$value] ?? null)
                    ->filter();

                return $label.': '.$names->join(', ');
            });
    }
}
