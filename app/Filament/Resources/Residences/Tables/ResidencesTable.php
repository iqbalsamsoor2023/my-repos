<?php

namespace App\Filament\Resources\Residences\Tables;

use App\Enums\Company\CompanyTypeEnum;
use App\Enums\Residence\ActivationStatusType;
use App\Enums\Residence\EntranceBarrierType;
use App\Enums\Residence\EntryLaneType;
use App\Enums\Residence\InternetProviderCompany;
use App\Enums\Residence\MoobanType;
use App\Enums\Residence\PropertyManagementType;
use App\Enums\Residence\SubType;
use App\Enums\User\RoleType;
use App\Exports\ResidenceExport;
use App\Helpers\CompanyHelper;
use App\Models\Residence;
use App\Services\FilamentExport\FilamentExportBulkAction;
use App\Services\FilamentExport\FilamentTableExportSupport;
use App\Support\BpoSoftwareSupplierFilterHelper;
use App\Support\ResidenceLocationFilterSupport;
use App\Support\ResidenceOptionsSupport;
use Carbon\Carbon;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class ResidencesTable
{
    public static function configure(Table $table): Table
    {
        $user = Filament::auth()->user();
        $activationStatusOptions = ResidenceOptionsSupport::activationStatusOptions();
        $supplierOptions = ResidenceOptionsSupport::bpoSupplierOptions();
        $securityGuardCountOptions = ResidenceOptionsSupport::securityGuardCountOptions();

        return $table
            ->columns([
                TextColumn::make('residence_activation_status_id')
                    ->label(__('app.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $activationStatusOptions[$state] ?? '-')
                    ->color(function ($state) {
                        return match ($state) {
                            1 => 'gray',
                            2 => 'red',
                            3 => 'pink',
                            4 => 'blue',
                            5 => 'green',
                            6 => 'orange',
                            default => 'black', // Fallback color
                        };
                    })
                    ->toggleable(),
                TextColumn::make('subdistrict.district.province.name_in_english')
                    ->description(fn (Residence $record): string => $record->subdistrict?->district?->province?->name_in_thai ?? '-')
                    ->label(__('app.province'))
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('subdistrict.district.name_in_english')
                    ->description(fn (Residence $record): string => $record->subdistrict?->district?->name_in_thai ?? '-')
                    ->label(__('app.district'))
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('subdistrict.name_in_english')
                    ->description(fn (Residence $record): string => $record->subdistrict?->name_in_thai ?? '-')
                    ->label(__('app.subdistrict'))
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('main_road')
                    ->badge()
                    ->label(__('residence.main_road'))
                    ->searchable(query: fn (Builder $query, string $search) => $query->where('residences.main_road', 'LIKE', "%{$search}%"))
                    ->toggleable(),
                TextColumn::make('mooban_type')
                    ->label(__('residence.mooban_type'))
                    ->formatStateUsing(function (string $state): string {
                        $moobanType = [
                            MoobanType::PUBLIC->value => Str::title(MoobanType::PUBLIC->name),
                            MoobanType::RESIDENCE->value => Str::title(MoobanType::RESIDENCE->name),
                            MoobanType::FACTORY->value => Str::title(MoobanType::FACTORY->name),
                            MoobanType::PMOC->value => Str::upper(MoobanType::PMOC->name),
                            MoobanType::DEMO_FOR_SG->value => str_replace('_', ' ', Str::title(MoobanType::DEMO_FOR_SG->name)),
                        ];

                        // Return the appropriate translation based on the state
                        return $moobanType[$state] ?? '-';
                    })
                    ->toggleable(),
                TextColumn::make('sub_type')
                    ->label(__('residence.house_type'))
                    ->formatStateUsing(function (?string $state): string {
                        if (! $state) {
                            return '-';
                        }

                        // Convert the value to enum and return its label
                        return SubType::tryFrom((int) $state)?->getLabel() ?? '-';
                    })
                    ->toggleable(),
                TextColumn::make('name')
                    ->label(__('residence.mooban_name'))
                    ->description(fn (Residence $record): string => $record->name_th ?? '-')
                    ->copyable()
                    ->searchable(query: function ($query, $search) {
                        $query->where('residences.name', 'like', "%{$search}%")
                            ->orWhere('residences.name_th', 'like', "%{$search}%");
                    })
                    ->toggleable(),
                TextColumn::make('propertyManagementUser.name')
                    ->label(__('residence.mooban_id'))
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('juritic_name')
                    ->label(__('residence.juristic_person'))
                    ->getStateUsing(function (Residence $record): string {
                        return $record->juristic_details['name'] ?? '-';
                    })
                    ->searchable(query: function ($query, $search) {
                        $query->where('residences.juristic_details->name', 'like', "%{$search}%");
                    })
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('juristic_phone')
                    ->label(__('residence.juristic_contact_no'))
                    ->getStateUsing(function (Residence $record): string {
                        return $record->juristic_details['phone_no'] ?? '-';
                    })
                    ->searchable(query: function ($query, $search) {
                        $query->where('residences.juristic_details->phone_no', 'like', "%{$search}%");
                    })
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('units_count')
                    ->counts('units')
                    ->label(__('unit.total_units'))
                    ->toggleable(),
                TextColumn::make('distinct_user_count')
                    ->label(__('app.total_residents'))
                    ->toggleable(),
                TextColumn::make('sign_up_percentage')
                    ->badge()
                    ->color(fn ($state, Residence $record): string => match (true) {
                        in_array($record->residence_activation_status_id, [
                            ActivationStatusType::INACTIVE_DEMO->value,
                            ActivationStatusType::ACTIVE_GT_ONLY->value,
                            ActivationStatusType::ACTIVE_DEMO->value,
                        ]) => 'gray',
                        (float) $state === 0 => 'gray',
                        (float) $state > 0 && (float) $state <= 25 => 'danger',
                        (float) $state > 25 && (float) $state <= 50 => 'warning',
                        (float) $state > 50 && (float) $state <= 75 => 'info',
                        (float) $state > 75 && (float) $state <= 100 => 'success',
                        default => 'gray',
                    })
                    ->label(__('residence.sign_up_percentage'))
                    ->getStateUsing(function (Residence $record): string {
                        if (in_array($record->residence_activation_status_id, [
                            ActivationStatusType::INACTIVE_DEMO->value,
                            ActivationStatusType::ACTIVE_GT_ONLY->value,
                            ActivationStatusType::ACTIVE_DEMO->value,
                        ])) {
                            return '0%';
                        }

                        $totalUnits = $record->units_count ?? 0;
                        $distinctUsers = $record->distinct_user_count ?? 0;

                        if ($totalUnits === 0) {
                            return '0%';
                        }

                        $percentage = round(($distinctUsers / $totalUnits) * 100, 2);

                        return $percentage.'%';
                    })
                    ->toggleable(),

                TextColumn::make('residence_age')
                    ->label(__('app.age'))
                    ->toggleable(),
                TextColumn::make('contracts')
                    ->label(__('app.contracts'))
                    ->alignCenter()
                    ->getStateUsing(fn (Residence $record) => $record->subscription_end_date
                        ? Carbon::parse($record->subscription_end_date)->format('d-M-y')
                        : '-'
                    )
                    ->description(function (Residence $record): HtmlString {
                        if (! $record->subscription_end_date) {
                            return new HtmlString('-');
                        }

                        $now = now();
                        $end = Carbon::parse($record->subscription_end_date);

                        $isExpired = $end->isPast();

                        $diff = $now->diff($end);

                        $parts = [];

                        if ($diff->y > 0) {
                            $parts[] = $diff->y.' year'.($diff->y > 1 ? 's' : '');
                        }

                        if ($diff->m > 0) {
                            $parts[] = $diff->m.' month'.($diff->m > 1 ? 's' : '');
                        }

                        if ($diff->d > 0 || empty($parts)) {
                            $parts[] = $diff->d.' day'.($diff->d > 1 ? 's' : '');
                        }

                        $text = implode(' ', $parts);

                        $text = $isExpired
                            ? "Expired {$text} ago"
                            : "{$text} left";

                        $daysDiff = $now->diffInDays($end, false);

                        [$bgColor, $textColor, $ringColor] = match (true) {
                            $daysDiff < 0 => [
                                'rgb(254 226 226)',
                                'rgb(185 28 28)',
                                'rgb(254 202 202)',
                            ],
                            $daysDiff <= 30 => [
                                'rgb(254 249 195)',
                                'rgb(161 98 7)',
                                'rgb(254 240 138)',
                            ],
                            default => [
                                'rgb(220 252 231)',
                                'rgb(21 128 61)',
                                'rgb(187 247 208)',
                            ],
                        };

                        return new HtmlString(sprintf(
                            '<span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 py-1"
                                style="background-color:%s;color:%s;border-color:%s">
                                <span class="truncate">%s</span>
                            </span>',
                            $bgColor,
                            $textColor,
                            $ringColor,
                            e($text)
                        ));
                    })
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('residence.created_at'))
                    ->getStateUsing(function (Residence $record) {
                        return $record->created_at->format('d-M-y H:i:s');
                    })
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label(__('residence.updated_at'))
                    ->getStateUsing(function (Residence $record) {
                        return $record->updated_at->format('d-M-y H:i:s');
                    })
                    ->toggleable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Filter::make('mooban')
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make(__('residence.filter_mooban_type_sub_type'))
                            ->columnSpanFull()
                            ->columns(3)
                            ->schema([
                                Select::make('mooban_type')
                                    ->options(collect(MoobanType::cases())->mapWithKeys(fn ($type) => [
                                        $type->value => $type->getLabel(),
                                    ]))
                                    ->preload()
                                    ->multiple(),

                                Select::make('sub_type')
                                    ->label(__('residence.house_type'))
                                    ->options(function (Get $get) {
                                        $moobanTypes = $get('mooban_type');

                                        if (! is_array($moobanTypes) || empty($moobanTypes)) {
                                            return [];
                                        }

                                        $filteredSubTypes = collect($moobanTypes)
                                            ->map(fn ($moobanType) => MoobanType::tryFrom((int) $moobanType)) // Ensure it's cast to int
                                            ->filter() // Remove null values (invalid enums)
                                            ->flatMap(fn ($moobanType) => $moobanType->allowedSubTypes() ?? []) // Get allowed subtypes
                                            ->unique();

                                        return $filteredSubTypes->mapWithKeys(fn ($subType) => [
                                            $subType->value => $subType->getLabel(),
                                        ])->toArray();
                                    })
                                    ->multiple(),
                                Select::make('name')
                                    ->label(__('app.residence_mooban'))
                                    ->options(fn (): array => ResidenceOptionsSupport::visibleResidenceOptions($user))
                                    ->searchable(),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                ! empty($data['mooban_type']),
                                fn (Builder $query) => $query->whereIn('residences.mooban_type', (array) $data['mooban_type'])
                            )
                            ->when(
                                ! empty($data['sub_type']),
                                fn (Builder $query) => $query->whereIn('residences.sub_type', (array) $data['sub_type'])

                            )
                            ->when(
                                $data['name'] ?? null,
                                fn (Builder $query): Builder => $query->whereId($data['name']),
                            );
                    }),
                ResidenceLocationFilterSupport::makeProvinceFilters(),
                Filter::make('status_filters')
                    ->label(__('app.status'))
                    ->visible(fn () => $user?->hasAnyRole([RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value]) ?? false)
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make(__('residence.filter_activation_status'))
                            ->columns(2)
                            ->schema([
                                Select::make('residence_activation_status_id')
                                    ->label(__('app.status'))
                                    ->options($activationStatusOptions)
                                    ->multiple(),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                ! empty($data['residence_activation_status_id']),
                                fn (Builder $query) => $query->whereIn('residences.residence_activation_status_id', $data['residence_activation_status_id'])
                            );
                    }),
                Filter::make('date_filters')
                    ->label(__('app.dates_filtering'))
                    ->visible(fn () => $user?->hasAnyRole([RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value]) ?? false)
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make(__('residence.filter_created_updated'))
                            ->columns(3)
                            ->schema([
                                DatePicker::make('created_from')
                                    ->label(__('app.created_from')),
                                DatePicker::make('created_until')
                                    ->label(__('app.created_until')),
                                DatePicker::make('updated_from')
                                    ->label(__('app.last_updated_from')),
                                DatePicker::make('updated_until')
                                    ->label(__('app.last_updated_until')),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                ! empty($data['created_from']),
                                fn (Builder $q) => $q->whereDate('residences.created_at', '>=', $data['created_from'])
                            )
                            ->when(
                                ! empty($data['created_until']),
                                fn (Builder $q) => $q->whereDate('residences.created_at', '<=', $data['created_until'])
                            )
                            ->when(
                                ! empty($data['updated_from']),
                                fn (Builder $q) => $q->whereDate('residences.updated_at', '>=', $data['updated_from'])
                            )
                            ->when(
                                ! empty($data['updated_until']),
                                fn (Builder $q) => $q->whereDate('residences.updated_at', '<=', $data['updated_until'])
                            );
                    }),
                Filter::make('companies')
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make(__('residence.filter_companies'))
                            ->columns(3)
                            ->schema([
                                Select::make('developer_id')
                                    ->label(__('app.developer_company'))
                                    ->searchable()
                                    ->options(CompanyHelper::getCompanyOptions(CompanyTypeEnum::DEVELOPER)),
                                Select::make('property_management_id')
                                    ->label(__('app.property_management_company'))
                                    ->searchable()
                                    ->options(CompanyHelper::getCompanyOptions(CompanyTypeEnum::PROPERTY_MANAGEMENT)),
                                Select::make('sgoc_company_id')
                                    ->label(__('user.security_guard_company'))
                                    ->searchable()
                                    ->options(CompanyHelper::getCompanyOptions(CompanyTypeEnum::SECURITY_GUARD)),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['developer_id'] ?? null, fn (Builder $query, $value) => $query->where('residences.developer_id', $value))
                            ->when($data['property_management_id'] ?? null, fn (Builder $query, $value) => $query->where('residences.property_management_id', $value))
                            ->when($data['sgoc_company_id'] ?? null, fn (Builder $query, $value) => $query->where('residences.sgoc_company_id', $value));
                    }),
                Filter::make('operations')
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make(__('residence.filter_operations_software'))
                            ->columns(3)
                            ->schema([
                                Select::make('property_management_types')
                                    ->label(__('app.pm_type'))
                                    ->options(PropertyManagementType::options())
                                    ->multiple()
                                    ->searchable(),
                                Select::make('security_guard_count')
                                    ->label(__('residence.number_of_security_guards'))
                                    ->options($securityGuardCountOptions)
                                    ->multiple()
                                    ->searchable(),
                                Select::make('software_vms_ids')
                                    ->label(__('residence.bpo_software_vms_supplier'))
                                    ->options($supplierOptions)
                                    ->multiple()
                                    ->searchable(),
                                Select::make('software_accounting_ids')
                                    ->label(__('residence.bpo_software_accounting_supplier'))
                                    ->options($supplierOptions)
                                    ->multiple()
                                    ->searchable(),
                                Select::make('software_apps_user_ids')
                                    ->label(__('residence.bpo_apps_user_supplier'))
                                    ->options($supplierOptions)
                                    ->multiple()
                                    ->searchable(),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                ! empty($data['property_management_types']),
                                fn (Builder $query) => $query->whereIn('residences.property_management_type', (array) $data['property_management_types'])
                            )
                            ->when(
                                ! empty($data['security_guard_count']),
                                fn (Builder $query) => $query->whereIn('residences.security_guard_count', array_map('intval', (array) $data['security_guard_count']))
                            )
                            ->when(
                                ! empty($data['software_vms_ids']),
                                fn (Builder $query) => BpoSoftwareSupplierFilterHelper::applyJsonFilter($query, 'vms', (array) $data['software_vms_ids'])
                            )
                            ->when(
                                ! empty($data['software_accounting_ids']),
                                fn (Builder $query) => BpoSoftwareSupplierFilterHelper::applyJsonFilter($query, 'accounting', (array) $data['software_accounting_ids'])
                            )
                            ->when(
                                ! empty($data['software_apps_user_ids']),
                                fn (Builder $query) => BpoSoftwareSupplierFilterHelper::applyJsonFilter($query, 'apps_user', (array) $data['software_apps_user_ids'])
                            );
                    }),

                Filter::make('residence_infrastructure_filters')
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make(__('residence.filter_infrastructure'))
                            ->columns(3)
                            ->schema([
                                Select::make('residence_age_category')
                                    ->label(__('app.age_category'))
                                    ->options([
                                        '1-5' => '1-5 years',
                                        '6-10' => '6-10 years',
                                        '11-15' => '11-15 years',
                                        '16-20' => '16-20 years',
                                        '21-25' => '21-25 years',
                                        '26-30' => '26-30 years',
                                        '31-35' => '31-35 years',
                                        '36-40' => '36-40 years',
                                        '41-45' => '41-45 years',
                                        '46-50' => '46-50 years',
                                        '50+' => '50+ years',
                                        'not_set' => __('user.not_yet_set'),
                                    ])
                                    ->searchable()
                                    ->preload(),
                                Select::make('guard_house_entry_number')
                                    ->label(__('residence.number_of_guard_house_entries'))
                                    ->options([
                                        1 => '1',
                                        2 => '2',
                                        3 => '3',
                                        4 => '4',
                                        5 => '5',
                                        'not_set' => __('user.not_yet_set'),
                                    ])
                                    ->native(false),
                                Select::make('guard_house_lane_type')
                                    ->label(__('residence.guard_house_lane_type'))
                                    ->options(EntryLaneType::options() + ['not_set' => __('user.not_yet_set')])
                                    ->searchable()
                                    ->preload(),
                                Select::make('guard_house_type')
                                    ->label(__('residence.guard_house_type'))
                                    ->options([
                                        0 => __('residence.no_roof'),
                                        1 => __('residence.has_roof_value'),
                                        'not_set' => __('user.not_yet_set'),
                                    ])
                                    ->searchable()
                                    ->preload(),
                                Select::make('entrance_barrier_type')
                                    ->label(__('residence.entrance_barrier_type'))
                                    ->options(EntranceBarrierType::options() + ['not_set' => __('user.not_yet_set')])
                                    ->searchable()
                                    ->preload(),
                                Select::make('internet_provider')
                                    ->label(__('residence.internet_provider'))
                                    ->options(fn () => InternetProviderCompany::options() + ['not_set' => __('user.not_yet_set')])
                                    ->searchable()
                                    ->preload(),
                                Select::make('has_cctv')
                                    ->label(__('residence.cctv_presence'))
                                    ->options([
                                        true => __('app.yes'),
                                        false => __('app.no'),
                                    ])
                                    ->native(false),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $currentYear = now()->year;

                        return $query
                            ->when(
                                $data['residence_age_category'] ?? null,
                                function (Builder $q, string $value) use ($currentYear) {
                                    if ($value === 'not_set') {
                                        return $q->whereNull('residences.completion_year');
                                    }

                                    [$min, $max] = match ($value) {
                                        '1-5' => [1, 5],
                                        '6-10' => [6, 10],
                                        '11-15' => [11, 15],
                                        '16-20' => [16, 20],
                                        '21-25' => [21, 25],
                                        '26-30' => [26, 30],
                                        '31-35' => [31, 35],
                                        '36-40' => [36, 40],
                                        '41-45' => [41, 45],
                                        '46-50' => [46, 50],
                                        '50+' => [51, PHP_INT_MAX],
                                    };

                                    $minCompletionYear = $currentYear - $max;
                                    $maxCompletionYear = $currentYear - $min;

                                    return $q->where('residences.completion_year', '<=', $maxCompletionYear)
                                        ->when($max !== PHP_INT_MAX, fn ($q2) => $q2->where('residences.completion_year', '>', $minCompletionYear));
                                }
                            )
                            ->when(
                                $data['guard_house_entry_number'] ?? null,
                                function (Builder $q, $value) {
                                    if ($value === 'not_set') {
                                        return $q->whereNull('residences.guard_house_entry_number');
                                    }

                                    return $q->where('residences.guard_house_entry_number', $value);
                                }
                            )
                            ->when(
                                $data['guard_house_lane_type'] ?? null,
                                function (Builder $q, $value) {
                                    if ($value === 'not_set') {
                                        return $q->whereNull('residences.guard_house_lane_type');
                                    }

                                    return $q->where('residences.guard_house_lane_type', $value);
                                }
                            )
                            ->when(
                                $data['guard_house_type'] ?? null,
                                function (Builder $q, $value) {
                                    if ($value === 'not_set') {
                                        return $q->whereNull('residences.has_roof');
                                    }

                                    return $q->where('residences.has_roof', (bool) $value);
                                }
                            )
                            ->when(
                                $data['entrance_barrier_type'] ?? null,
                                function (Builder $q, $value) {
                                    if ($value === 'not_set') {
                                        return $q->whereNull('residences.entrance_barrier_type');
                                    }

                                    return $q->where('residences.entrance_barrier_type', $value);
                                }
                            )
                            ->when(
                                $data['internet_provider'] ?? null,
                                function (Builder $q, $value) {
                                    if ($value === 'not_set') {
                                        return $q->where(function ($q) {
                                            $q->whereNull('residences.internet_provider_id')->orWhereJsonLength('residences.internet_provider_id', 0);
                                        });
                                    }

                                    return $q->whereJsonContains('residences.internet_provider_id', (string) $value);
                                }
                            )
                            ->when(
                                isset($data['has_cctv']),
                                fn (Builder $q) => $q->where('residences.has_cctv', filter_var($data['has_cctv'], FILTER_VALIDATE_BOOLEAN))
                            );
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                FilamentExportBulkAction::make('export')
                    ->fileName('Residence-Report')
                    ->disableAdditionalColumns()
                    ->disableCsv()
                    ->disablePdf()
                    ->disableFilterColumns()
                    ->action(function (Component $livewire) {
                        $columns = FilamentTableExportSupport::visibleColumnNames($livewire);
                        $fileNameInput = $livewire->mountedActions[0]['data']['file_name'] ?? 'export';
                        $fileName = FilamentTableExportSupport::excelFileName($fileNameInput, 'export', true);
                        $selectedResidenceIds = FilamentTableExportSupport::selectedRecordIds($livewire);
                        $residences = Residence::whereIn('id', $selectedResidenceIds, 'and', false)->latest('id')->get();

                        FilamentTableExportSupport::recordExportAudit(
                            Filament::auth()->user(),
                            'exported residence records'
                        );

                        return Excel::download(new ResidenceExport($residences, $columns), $fileName);
                    }),
            ])
            ->paginated([10, 25, 50]);
    }
}
