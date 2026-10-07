<?php

namespace App\Filament\Resources\ResidenceInfos\Tables;

use App\Enums\Company\CompanyTypeEnum;
use App\Enums\Residence\EntranceBarrierType;
use App\Enums\Residence\EntryLaneType;
use App\Enums\Residence\InternetProviderCompany;
use App\Enums\Residence\MoobanType;
use App\Enums\User\RoleType;
use App\Exports\ResidenceExport;
use App\Helpers\CompanyHelper;
use App\Models\Residence;
use App\Services\FilamentExport\FilamentExportBulkAction;
use App\Services\FilamentExport\FilamentTableExportSupport;
use App\Support\ResidenceLocationFilterSupport;
use App\Support\ResidenceOptionsSupport;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class ResidenceInfosTable
{
    public static function configure(Table $table): Table
    {
        $user = Filament::auth()->user();
        $activationStatusOptions = ResidenceOptionsSupport::activationStatusOptions();

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
                TextColumn::make('name')
                    ->label(__('residence.mooban_name'))
                    ->description(fn (Residence $record): string => $record->name_th ?? '-')
                    ->copyable()
                    ->searchable(query: function ($query, $search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('name_th', 'like', "%{$search}%");
                    })
                    ->toggleable(),
                TextColumn::make('propertyManagementUser.name')
                    ->label(__('residence.mooban_id'))
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('guard_house_entry_number')
                    ->label(__('residence.number_of_guard_house_entries'))
                    ->badge()
                    ->numeric()
                    ->toggleable(),

                IconColumn::make('has_roof')
                    ->label(__('residence.guard_house_type'))
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('guard_house_lane_type')
                    ->label(__('residence.guard_house_lane_type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => EntryLaneType::tryFrom($state)?->getLabel() ?? '-')
                    ->toggleable(),

                TextColumn::make('entrance_barrier_type')
                    ->label(__('residence.entrance_barrier_type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => EntranceBarrierType::tryFrom($state)?->label() ?? '-')
                    ->toggleable(),

                TextColumn::make('cctv_count')
                    ->label(__('residence.cctv_presence'))
                    ->numeric()
                    ->toggleable(),
                TextColumn::make('internet_provider_id')
                    ->label(__('residence.internet_provider'))
                    ->badge()
                    ->formatStateUsing(
                        fn ($state) => collect(json_decode($state, true))
                            ->map(fn ($id) => InternetProviderCompany::tryFrom($id)?->label())
                            ->filter()
                            ->implode(', ')
                    )
                    ->color(fn ($state) => 'gray')
                    ->icons([
                        'heroicon-o-globe-alt' => fn ($state) => ! empty($state),
                    ])
                    ->extraAttributes(['class' => 'whitespace-normal']),
                ImageColumn::make('cover_image')
                    ->label(__('app.cover_image'))
                    ->defaultImageUrl(fn ($record) => $record->getFirstMediaUrl('cover_image') ?: 'https://via.placeholder.com/250')
                    ->imageSize(size: 250)
                    ->extraAttributes(['class' => 'rounded-md shadow']),

                ImageColumn::make('google_map_image')
                    ->label(__('residence.google_map_image'))
                    ->defaultImageUrl(fn ($record) => $record->getFirstMediaUrl('google_map_image') ?: 'https://via.placeholder.com/250')
                    ->imageSize(250)
                    ->extraAttributes(['class' => 'rounded-md shadow']),

                ImageColumn::make('guard_house_image')
                    ->label(__('residence.guard_house_image'))
                    ->defaultImageUrl(fn ($record) => $record->getFirstMediaUrl('guard_house_image') ?: 'https://via.placeholder.com/250')
                    ->imageSize(250)
                    ->extraAttributes(['class' => 'rounded-md shadow']),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Filter::make('mooban')
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make(__('residence.filter_mooban_type_sub_type'))
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
                                fn (Builder $query) => $query->whereIn('mooban_type', (array) $data['mooban_type'])
                            )
                            ->when(
                                ! empty($data['sub_type']),
                                fn (Builder $query) => $query->whereIn('sub_type', (array) $data['sub_type'])

                            )
                            ->when(
                                $data['name'] ?? null,
                                fn (Builder $query): Builder => $query->whereId($data['name']),
                            );
                    }),
                ResidenceLocationFilterSupport::makeProvinceFilters(
                    mainRoadColumn: 'main_road',
                ),
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
                                fn (Builder $query) => $query->whereIn('residence_activation_status_id', $data['residence_activation_status_id'])
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
                                fn (Builder $q) => $q->whereDate('created_at', '>=', $data['created_from'])
                            )
                            ->when(
                                ! empty($data['created_until']),
                                fn (Builder $q) => $q->whereDate('created_at', '<=', $data['created_until'])
                            )
                            ->when(
                                ! empty($data['updated_from']),
                                fn (Builder $q) => $q->whereDate('updated_at', '>=', $data['updated_from'])
                            )
                            ->when(
                                ! empty($data['updated_until']),
                                fn (Builder $q) => $q->whereDate('updated_at', '<=', $data['updated_until'])
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
                        collect([
                            'developer_id' => 'developer_id',
                            'property_management_id' => 'property_management_id',
                            'sgoc_company_id' => 'sgoc_company_id',
                        ])->each(function ($column, $field) use ($query, $data) {
                            if (! empty($data[$field])) {
                                $query->where($column, $data[$field]);
                            }
                        });

                        return $query;
                    }),
                Filter::make('residence_infrastructure_filters')
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make(__('residence.filter_infrastructure'))
                            ->columns(3)
                            ->schema([
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
                                        return $q->whereNull('completion_year');
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

                                    return $q->where('completion_year', '<=', $maxCompletionYear)
                                        ->when($max !== PHP_INT_MAX, fn ($q2) => $q2->where('completion_year', '>', $minCompletionYear));
                                }
                            )
                            ->when(
                                $data['guard_house_entry_number'] ?? null,
                                function (Builder $q, $value) {
                                    if ($value === 'not_set') {
                                        return $q->whereNull('guard_house_entry_number');
                                    }

                                    return $q->where('guard_house_entry_number', $value);
                                }
                            )
                            ->when(
                                $data['guard_house_lane_type'] ?? null,
                                function (Builder $q, $value) {
                                    if ($value === 'not_set') {
                                        return $q->whereNull('guard_house_lane_type');
                                    }

                                    return $q->where('guard_house_lane_type', $value);
                                }
                            )
                            ->when(
                                $data['guard_house_type'] ?? null,
                                function (Builder $q, $value) {
                                    if ($value === 'not_set') {
                                        return $q->whereNull('has_roof');
                                    }

                                    return $q->where('has_roof', (bool) $value);
                                }
                            )
                            ->when(
                                $data['entrance_barrier_type'] ?? null,
                                function (Builder $q, $value) {
                                    if ($value === 'not_set') {
                                        return $q->whereNull('entrance_barrier_type');
                                    }

                                    return $q->where('entrance_barrier_type', $value);
                                }
                            )
                            ->when(
                                $data['internet_provider'] ?? null,
                                function (Builder $q, $value) {
                                    if ($value === 'not_set') {
                                        return $q->where(function ($q) {
                                            $q->whereNull('internet_provider_id')->orWhereJsonLength('internet_provider_id', 0);
                                        });
                                    }

                                    return $q->whereJsonContains('internet_provider_id', (string) $value);
                                }
                            )
                            ->when(
                                isset($data['has_cctv']),
                                fn (Builder $q) => $q->where('has_cctv', filter_var($data['has_cctv'], FILTER_VALIDATE_BOOLEAN))
                            );
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
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
