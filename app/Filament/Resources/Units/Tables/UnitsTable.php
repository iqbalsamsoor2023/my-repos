<?php

namespace App\Filament\Resources\Units\Tables;

use App\Enums\ResalesManagement\ResalesManagementStatus;
use App\Enums\Residence\LocationTagType;
use App\Enums\Residence\MoobanType;
use App\Enums\SalesManagement\ConstructionProgressEnum;
use App\Enums\TenancyManagement\TenancyManagementStatus;
use App\Enums\Unit\StatusType;
use App\Exports\UnitExport;
use App\Models\ErpLocationTag;
use App\Models\ResidenceActivationStatus;
use App\Models\Unit;
use App\Models\UnitStatsView;
use App\Policies\UnitPolicy;
use App\Services\FilamentExport\FilamentExportBulkAction;
use App\Services\FilamentExport\FilamentTableExportSupport;
use App\Services\ResidenceService;
use App\Services\ThailandLocationService;
use Closure;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class UnitsTable
{
    public static function configure(Table $table): Table
    {
        $user = Auth::user();
        $signUpOptions = [
            'signed_up' => __('unit.signed_up'),
            'not_signed_up' => __('unit.not_signed_up'),
        ];
        $ownershipStatusOptions = [
            'owner' => __('app.owner'),
            'tenant' => __('app.tenant'),
        ];

        return $table
            ->columns([
                TextColumn::make('residence.name')
                    ->label(__('app.mooban_or_residence'))
                    ->icon('heroicon-o-square-2-stack')
                    ->copyable()
                    ->searchable()
                    ->toggleable()
                    ->description(fn (Unit $record): string => $record?->residence?->name_th ?? '-'),
                TextColumn::make('unit_number')
                    ->label(__('unit.unit_number'))
                    ->icon('heroicon-o-square-2-stack')
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('home_id')
                    ->label(__('app.home_id'))
                    ->icon('heroicon-o-square-2-stack')
                    ->copyable()
                    ->visible(fn () => UnitPolicy::hasPmDashboardAccess($user))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('move_in_at')
                    ->label(__('unit.move_in_at'))
                    ->getStateUsing(function (Unit $record): ?string {
                        if (blank($record->move_in_at)) {
                            return null;
                        }

                        return date('d-M-y', strtotime((string) $record->move_in_at));
                    })
                    ->toggleable(),
                TextColumn::make('invitation_code_owner')
                    ->label(__('unit.invitation_code_owner'))
                    ->icon('heroicon-o-square-2-stack')
                    ->copyable()
                    ->visible(fn () => UnitPolicy::hasPmDashboardAccess($user))
                    ->toggleable(),
                TextColumn::make('invitation_code_tenant')
                    ->label(__('unit.invitation_code_tenant'))
                    ->icon('heroicon-o-square-2-stack')
                    ->visible(fn () => UnitPolicy::hasPmDashboardAccess($user))
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('block')
                    ->label(__('unit.block'))
                    ->toggleable(),
                TextColumn::make('street')
                    ->label(__('unit.soi'))
                    ->toggleable(),
                TextColumn::make('floor')
                    ->label(__('unit.floor'))
                    ->toggleable(),
                TextColumn::make('space_size')
                    ->label(__('unit.space_size'))
                    ->toggleable(),
                TextColumn::make('land_size')
                    ->label(__('unit.land_size'))
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) : '-'),
                TextColumn::make('charge_type')
                    ->label(__('unit.charge_type'))
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? $state->getLabel() : '-'),
                TextColumn::make('maintenance_cycle')
                    ->label(__('unit.maintenance_cycle'))
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? $state->getLabel() : '-'),
                TextColumn::make('status')
                    ->label(__('app.status'))
                    ->icon('heroicon-o-square-2-stack')
                    ->copyable()
                    ->formatStateUsing(fn (?string $state): string => StatusType::tryFrom($state)?->getLabel() ?? '-')
                    ->toggleable(),
                TextColumn::make('rentAdvertisement.tenancy_status')
                    ->label(__('resales-and-tenancies.tenancy_status'))
                    ->icon('heroicon-o-square-2-stack')
                    ->formatStateUsing(fn (?int $state): string => TenancyManagementStatus::tryFrom($state)?->getLabel() ?? '-'),
                TextColumn::make('resaleAdvertisement.status')
                    ->label(__('resales-and-tenancies.resale_status'))
                    ->icon('heroicon-o-square-2-stack')
                    ->formatStateUsing(fn (?int $state): string => ResalesManagementStatus::tryFrom($state)?->getLabel() ?? '-'),
                TextColumn::make('construction_progress')
                    ->label(__('unit.construction_progress'))
                    ->formatStateUsing(fn (?int $state): string => ConstructionProgressEnum::tryFrom($state)?->getLabel() ?? '-')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label(__('app.created_at'))
                    ->getStateUsing(function (Unit $record) {
                        return $record->created_at?->format('d-M-y H:i:s');
                    })
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label(__('app.updated_at'))
                    ->getStateUsing(function (Unit $record) {
                        return $record->updated_at?->format('d-M-y H:i:s');
                    })
                    ->toggleable(),

            ])
            ->defaultSort('updated_at', 'desc')
            ->paginationMode(PaginationMode::Default)
            ->defaultPaginationPageOption(10)
            ->filters([
                Filter::make('unit_search')
                    ->label(__('app.unit_search'))
                    ->visible(fn () => UnitPolicy::isPropertyManager($user))
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make('Unit & Living Status')
                            ->columns(2)
                            ->schema([
                                TextInput::make('unit_number')
                                    ->label(__('unit.unit_number'))
                                    ->placeholder(__('unit.unit_number')),
                                Select::make('status')
                                    ->label(__('unit.living_status'))
                                    ->options(fn () => StatusType::options())
                                    ->multiple(),
                                Select::make('sign_up')
                                    ->label(__('unit.sign_up_status'))
                                    ->options($signUpOptions),
                                Select::make('ownership_status')
                                    ->label(__('unit.ownership_status'))
                                    ->options($ownershipStatusOptions),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! empty($data['unit_number'])) {
                            static::applyUnitStatsViewPrefilter(
                                $query,
                                fn ($statsQuery) => $statsQuery->where('unit_number', 'LIKE', '%'.$data['unit_number'].'%')
                            );
                        }

                        if (! empty($data['status'])) {
                            static::applyUnitStatsViewPrefilter(
                                $query,
                                fn ($statsQuery) => $statsQuery->whereIn('status', (array) $data['status'])
                            );
                        }

                        $signUp = $data['sign_up'] ?? null;
                        if ($signUp === 'signed_up' || $signUp === 'not_signed_up') {
                            $query = static::applySignUpFilter($query, $signUp);
                        }

                        $ownership = $data['ownership_status'] ?? null;
                        if ($ownership === 'owner' || $ownership === 'tenant') {
                            $query = static::applyOwnershipFilter($query, $ownership);
                        }

                        return $query;
                    }),
                Filter::make('mooban')
                    ->visible(fn () => ! UnitPolicy::isPropertyManager($user))
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make('Mooban Type & Sub Type')
                            ->columns(2)
                            ->schema([
                                Select::make('mooban_type')
                                    ->options(MoobanType::class)
                                    ->preload()
                                    ->multiple(),
                                Select::make('sub_type')
                                    ->label(__('residence.house_type'))
                                    ->options(function (Get $get) {
                                        $moobanTypes = (array) $get('mooban_type');

                                        if (empty($moobanTypes)) {
                                            return [];
                                        }

                                        $filteredSubTypes = collect($moobanTypes)
                                            ->flatMap(function ($moobanType) {
                                                $enum = $moobanType instanceof MoobanType
                                                    ? $moobanType
                                                    : MoobanType::tryFrom($moobanType);

                                                return $enum?->allowedSubTypes() ?? [];
                                            })
                                            ->unique();

                                        return $filteredSubTypes->mapWithKeys(fn ($subType) => [
                                            $subType->value => $subType->getLabel(),
                                        ])->toArray();
                                    })
                                    ->multiple(),
                                Select::make('residence_id')
                                    ->label(__('unit.residence_mooban'))
                                    ->searchable()
                                    ->optionsLimit(50)
                                    ->getSearchResultsUsing(fn (string $search): array => ResidenceService::searchResidencesForUser($search))
                                    ->getOptionLabelUsing(fn ($value): ?string => ResidenceService::getResidenceLabelForUser($value))
                                    ->hidden(fn () => UnitPolicy::isPropertyManager(Auth::user())),
                                Select::make('unit_number')
                                    ->label(__('unit.unit_number'))
                                    ->options(function (Get $get) {
                                        $residenceId = $get('residence_id');

                                        if (empty($residenceId)) {
                                            return [];
                                        }

                                        return Unit::where('residence_id', '=', $residenceId, 'and')
                                            ->orderBy('unit_number', 'asc')
                                            ->pluck('unit_number', 'unit_number')
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->helperText(__('unit.select_residence_first')),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $moobanTypes = array_map(
                            fn ($value) => $value instanceof MoobanType ? $value->value : $value,
                            (array) ($data['mooban_type'] ?? [])
                        );

                        if (! empty($moobanTypes)) {
                            static::applyResidencePrefilter(
                                $query,
                                fn ($residenceQuery) => $residenceQuery->whereIn('mooban_type', $moobanTypes)
                            );
                        }

                        if (! empty($data['sub_type'])) {
                            static::applyUnitStatsViewPrefilter(
                                $query,
                                fn ($statsQuery) => $statsQuery->whereIn('sub_type', (array) $data['sub_type'])
                            );
                        }

                        if (! empty($data['residence_id'])) {
                            static::applyUnitStatsViewPrefilter(
                                $query,
                                fn ($statsQuery) => $statsQuery->where('residence_id', $data['residence_id'])
                            );
                        }

                        if (! empty($data['unit_number'])) {
                            static::applyUnitStatsViewPrefilter(
                                $query,
                                fn ($statsQuery) => $statsQuery->where('unit_number', 'LIKE', '%'.$data['unit_number'].'%')
                            );
                        }

                        return $query;
                    }),
                Filter::make('province_filters')
                    ->visible(fn () => UnitPolicy::isGlobalAdmin(Auth::user()))
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make('Province, District, Subdistrict & Main Road')
                            ->columns(3)
                            ->schema([
                                Select::make('province')
                                    ->label(__('app.province'))
                                    ->options(fn () => ThailandLocationService::getProvinces())
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->live()
                                    ->multiple(),

                                Select::make('district')
                                    ->label(__('app.district'))
                                    ->options(function (Get $get) {
                                        $provinceIds = (array) $get('province');

                                        if (empty($provinceIds)) {
                                            return [];
                                        }

                                        return ThailandLocationService::getDistrictsByProvinces($provinceIds);
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->live()
                                    ->multiple(),

                                Select::make('subdistrict')
                                    ->label(__('app.subdistrict'))
                                    ->options(function (Get $get) {
                                        $districtIds = (array) $get('district');

                                        if (empty($districtIds)) {
                                            return [];
                                        }

                                        return ThailandLocationService::getSubdistrictsByDistricts($districtIds);
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->live()
                                    ->multiple(),

                                Select::make('main_road')
                                    ->label(__('residence.main_road'))
                                    ->options(function (Get $get) {
                                        return ThailandLocationService::getMainRoadsByDistricts(
                                            array_map('intval', array_filter((array) $get('district')))
                                        );
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! empty($data['subdistrict'])) {
                            static::applyUnitStatsViewPrefilter(
                                $query,
                                fn ($statsQuery) => $statsQuery->whereIn('subdistrict_id', (array) $data['subdistrict'])
                            );
                        } elseif (! empty($data['district'])) {
                            static::applyUnitStatsViewPrefilter(
                                $query,
                                fn ($statsQuery) => $statsQuery->whereIn('district_id', (array) $data['district'])
                            );
                        } elseif (! empty($data['province'])) {
                            static::applyUnitStatsViewPrefilter(
                                $query,
                                fn ($statsQuery) => $statsQuery->whereIn('province_id', (array) $data['province'])
                            );
                        }

                        if (! empty($data['main_road'])) {
                            $mainRoadIds = DB::table('residences')
                                ->where('main_road', 'LIKE', '%'.$data['main_road'].'%')
                                ->whereNull('deleted_at')
                                ->pluck('id')
                                ->toArray();
                            $query->whereIn('units.residence_id', empty($mainRoadIds) ? [0] : $mainRoadIds);
                        }

                        return $query;
                    }),
                Filter::make('status_filters')
                    ->label(__('app.status'))
                    ->visible(fn () => ! UnitPolicy::isPropertyManager($user))
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make('Activation, Living Status & Ownership')
                            ->columns(2)
                            ->schema([
                                Select::make('residence_activation_status_id')
                                    ->label(__('app.status'))
                                    ->options(fn () => ResidenceActivationStatus::pluck('status', 'id')->toArray())
                                    ->multiple(),
                                Select::make('status')
                                    ->label(__('unit.living_status'))
                                    ->options(fn () => StatusType::options())
                                    ->multiple(),
                                Select::make('sign_up')
                                    ->label(__('unit.sign_up_status'))
                                    ->options($signUpOptions),
                                Select::make('ownership_status')
                                    ->label(__('unit.ownership_status'))
                                    ->options($ownershipStatusOptions),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! empty($data['residence_activation_status_id'])) {
                            static::applyResidencePrefilter(
                                $query,
                                fn ($residenceQuery) => $residenceQuery->whereIn(
                                    'residence_activation_status_id',
                                    (array) $data['residence_activation_status_id']
                                )
                            );
                        }

                        if (! empty($data['status'])) {
                            static::applyUnitStatsViewPrefilter(
                                $query,
                                fn ($statsQuery) => $statsQuery->whereIn('status', (array) $data['status'])
                            );
                        }

                        if (! empty($data['residence_activation_status_id'])) {
                            static::applyUnitStatsViewPrefilter(
                                $query,
                                fn ($statsQuery) => $statsQuery->whereIn(
                                    'residence_activation_status_id',
                                    (array) $data['residence_activation_status_id']
                                )
                            );
                        }

                        $signUp = $data['sign_up'] ?? null;
                        if ($signUp === 'signed_up' || $signUp === 'not_signed_up') {
                            $query = static::applySignUpFilter($query, $signUp);
                        }

                        $ownership = $data['ownership_status'] ?? null;
                        if ($ownership === 'owner' || $ownership === 'tenant') {
                            $query = static::applyOwnershipFilter($query, $ownership);
                        }

                        return $query;
                    }),
                Filter::make('date_filters')
                    ->label(__('app.dates_filtering'))
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make('Created & Updated Filter')
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
                        if (! empty($data['created_from'])) {
                            static::applyUnitStatsViewPrefilter(
                                $query,
                                fn ($statsQuery) => $statsQuery->whereDate('unit_created_at', '>=', $data['created_from'])
                            );
                        }

                        if (! empty($data['created_until'])) {
                            static::applyUnitStatsViewPrefilter(
                                $query,
                                fn ($statsQuery) => $statsQuery->whereDate('unit_created_at', '<=', $data['created_until'])
                            );
                        }

                        if (! empty($data['updated_from'])) {
                            static::applyUnitStatsViewPrefilter(
                                $query,
                                fn ($statsQuery) => $statsQuery->whereDate('unit_updated_at', '>=', $data['updated_from'])
                            );
                        }

                        if (! empty($data['updated_until'])) {
                            static::applyUnitStatsViewPrefilter(
                                $query,
                                fn ($statsQuery) => $statsQuery->whereDate('unit_updated_at', '<=', $data['updated_until'])
                            );
                        }

                        return $query;
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make()
                        ->modalHeading(fn (Unit $record): string => "Delete unit {$record->unit_number}")
                        ->after(fn (Unit $record) => self::updateInvitationCode($record)),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                FilamentExportBulkAction::make('export')
                    ->fileName('Unit-Report')
                    ->modalSubmitAction(false)
                    ->disableAdditionalColumns()
                    ->disableCsv()
                    ->disablePdf()
                    ->disableFilterColumns()
                    ->action(function (array $data, Component $livewire) {
                        $columns = FilamentTableExportSupport::visibleColumnNames($livewire);
                        $fileName = FilamentTableExportSupport::excelFileName($data['file_name'] ?? null, 'Unit-Report');
                        $selectedUnitIds = FilamentTableExportSupport::selectedRecordIds($livewire);

                        $units = Unit::whereIn('id', $selectedUnitIds, 'and', false)
                            ->orderBy('id', 'asc')
                            ->get();

                        FilamentTableExportSupport::recordExportAudit(Auth::user(), 'exported unit records');

                        return Excel::download(new UnitExport($units, $columns), $fileName);
                    }),
                DeleteBulkAction::make()
                    ->after(function (Component $livewire): void {
                        $records = $livewire->getSelectedTableRecords();

                        foreach ($records as $record) {
                            self::updateInvitationCode($record);
                        }
                    }),
            ]);
    }

    private static function applySignUpFilter(Builder $query, string $signUpStatus): Builder
    {
        if ($signUpStatus === 'signed_up') {
            return $query
                ->whereIn('units.id', UnitStatsView::query()
                    ->where('is_signed_up', true)
                    ->select('unit_id'))
                ->whereExists(fn ($sub) => $sub->from('unit_user as uu')
                    ->join('users as usr', 'usr.id', '=', 'uu.user_id')
                    ->whereColumn('uu.unit_id', 'units.id')
                    ->whereNull('uu.deleted_at')
                    ->whereNull('usr.deleted_at'));
        }

        return $query->whereNotExists(
            fn ($sub) => $sub->from('unit_user as uu')
                ->whereColumn('uu.unit_id', 'units.id')
                ->whereNull('uu.deleted_at')
        );
    }

    private static function applyOwnershipFilter(Builder $query, string $ownershipStatus): Builder
    {
        $isOwner = $ownershipStatus === 'owner';
        $prefilterColumn = $isOwner ? 'is_registered_owner' : 'is_registered_tenant';
        $isOwnerFlag = $isOwner ? 1 : 0;

        return $query
            ->whereIn('units.id', UnitStatsView::query()
                ->where($prefilterColumn, true)
                ->select('unit_id'))
            ->whereExists(function ($sub) use ($isOwnerFlag) {
                $sub->from('unit_user as uu')
                    ->whereColumn('uu.unit_id', 'units.id')
                    ->whereNull('uu.deleted_at')
                    ->where('uu.is_owner', $isOwnerFlag);
            });
    }

    private static function applyUnitStatsViewPrefilter(Builder $query, Closure $callback): Builder
    {
        $subquery = UnitStatsView::query()->select('unit_id');
        $callback($subquery);

        return $query->whereIn('units.id', $subquery);
    }

    private static function applyResidencePrefilter(Builder $query, Closure $callback): Builder
    {
        $subquery = DB::table('residences')
            ->select('id')
            ->whereNull('deleted_at');
        $callback($subquery);

        return $query->whereIn('units.residence_id', $subquery);
    }

    private static function updateInvitationCode(Unit $record): void
    {
        $record->update([
            'invitation_code_owner' => $record->invitation_code_owner.'_deleted_'.date('Ymd_His'),
            'invitation_code_tenant' => $record->invitation_code_tenant.'_deleted_'.date('Ymd_His'),
        ]);
    }
}
