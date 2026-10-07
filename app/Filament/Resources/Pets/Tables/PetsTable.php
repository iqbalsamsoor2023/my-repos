<?php

namespace App\Filament\Resources\Pets\Tables;

use App\Enums\Pet\PetType;
use App\Enums\Residence\MoobanType;
use App\Exports\PetExport;
use App\Models\Pet;
use App\Models\ResidenceActivationStatus;
use App\Policies\PetPolicy;
use App\Services\FilamentExport\FilamentExportBulkAction;
use App\Services\FilamentExport\FilamentTableExportSupport;
use App\Services\ThailandLocationService;
use App\Support\QueryGuardSupport;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class PetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('unit.residence.name')
                    ->label(__('app.mooban_or_residence'))
                    ->copyable()
                    ->toggleable()
                    ->description(fn (Pet $record): string => $record?->unit?->residence?->name_th ?? '-')
                    ->hidden(fn (): bool => PetPolicy::isPropertyManager(Auth::user())),
                TextColumn::make('unit.unit_number')
                    ->label(__('unit.unit_number'))
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('user.name')
                    ->label(__('user.owner'))
                    ->copyable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('type')
                    ->label(__('app.type'))
                    ->formatStateUsing(fn ($state) => ucfirst(strtolower($state)))
                    ->toggleable(),
                TextColumn::make('breed')
                    ->label(__('pet.breed'))
                    ->toggleable(),
                TextColumn::make('pet_age')
                    ->label(__('app.age'))
                    ->toggleable(),
                ColumnGroup::make(__('app.created_at'), [
                    TextColumn::make('created_at_date')
                        ->label(__('app.date'))
                        ->getStateUsing(function (Pet $record) {
                            return $record->created_at->format('d-M-y');
                        }),
                    TextColumn::make('created_at_time')
                        ->label(__('app.time'))
                        ->getStateUsing(function (Pet $record) {
                            return $record->created_at->format('H:i:s');
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('residence_filters')
                    ->visible(fn () => PetPolicy::isGlobalAdmin(Filament::auth()->user()))
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make('Mooban Type & Sub Type')
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
                                        $moobanTypes = (array) $get('mooban_type');

                                        if (empty($moobanTypes)) {
                                            return [];
                                        }

                                        $filteredSubTypes = collect($moobanTypes)
                                            ->flatMap(fn ($moobanType) => MoobanType::tryFrom($moobanType)?->allowedSubTypes() ?? [])
                                            ->unique();

                                        return $filteredSubTypes->mapWithKeys(fn ($subType) => [
                                            $subType->value => $subType->getLabel(),
                                        ])->toArray();
                                    })
                                    ->multiple(),
                                Select::make('residence_id')
                                    ->label(__('app.residence_mooban'))
                                    ->options(list_residences())
                                    ->searchable(),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                ! empty($data['mooban_type']),
                                fn (Builder $query) => $query->whereHas(
                                    'unit.residence',
                                    fn ($q) => $q->whereIn('mooban_type', (array) $data['mooban_type'])
                                )
                            )
                            ->when(
                                ! empty($data['sub_type']),
                                fn (Builder $query) => $query->whereHas(
                                    'unit.residence',
                                    fn ($q) => $q->whereIn('sub_type', (array) $data['sub_type'])
                                )

                            )
                            ->when(
                                $data['residence_id'] ?? null,
                                fn (Builder $query) => $query->whereHas(
                                    'unit.residence',
                                    fn ($q) => $q->where('id', $data['residence_id'])
                                )
                            );
                    }),
                Filter::make('province_filters')
                    ->visible(fn () => PetPolicy::isGlobalAdmin(Filament::auth()->user()))
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
                                    ->live()
                                    ->multiple(),

                                Select::make('district')
                                    ->label(__('app.district'))
                                    ->options(function (Get $get) {
                                        $provinceIds = array_map('intval', array_filter((array) $get('province')));

                                        if (empty($provinceIds)) {
                                            return [];
                                        }

                                        return ThailandLocationService::getDistrictsByProvinces($provinceIds);
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->multiple(),

                                Select::make('subdistrict')
                                    ->label(__('app.subdistrict'))
                                    ->options(function (Get $get) {
                                        $districtIds = array_map('intval', array_filter((array) $get('district')));

                                        if (empty($districtIds)) {
                                            return [];
                                        }

                                        return ThailandLocationService::getSubdistrictsByDistricts($districtIds);
                                    })
                                    ->searchable()
                                    ->preload()
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
                                    ->live(),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $provinceIds = array_map('intval', array_filter((array) ($data['province'] ?? [])));
                        $districtIds = array_map('intval', array_filter((array) ($data['district'] ?? [])));
                        $subdistrictIds = array_map('intval', array_filter((array) ($data['subdistrict'] ?? [])));
                        $residenceIds = ThailandLocationService::getResidenceIdsByLocation($provinceIds, $districtIds, $subdistrictIds);
                        $hasLocationFilters = ! empty($provinceIds) || ! empty($districtIds) || ! empty($subdistrictIds);

                        return $query
                            ->when(
                                $hasLocationFilters,
                                function (Builder $builder) use ($residenceIds): Builder {
                                    return QueryGuardSupport::whereHasInOrDenyAll($builder, 'unit.residence', 'id', $residenceIds);
                                }
                            )
                            ->when(
                                $data['main_road'] ?? null,
                                fn (Builder $builder): Builder => $builder->whereHas('unit.residence', function ($q) use ($data) {
                                    return $q->where('main_road', 'LIKE', '%'.$data['main_road'].'%');
                                })
                            );
                    }),
                Filter::make('status_filters')
                    ->label(__('app.status'))
                    ->visible(fn () => PetPolicy::isGlobalAdmin(Filament::auth()->user()))
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make('Activation Status')
                            ->columns(2)
                            ->schema([
                                Select::make('residence_activation_status_id')
                                    ->label(__('app.status'))
                                    ->options(fn () => ResidenceActivationStatus::pluck('status', 'id')->toArray())
                                    ->multiple(),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                ! empty($data['residence_activation_status_id']),
                                fn (Builder $query) => $query->whereHas('unit.residence', function ($q) use ($data) {
                                    $q->whereIn('residence_activation_status_id', $data['residence_activation_status_id']);
                                })
                            );
                    }),
                Filter::make('date_filters')
                    ->label(__('app.dates_filtering'))
                    ->visible(fn () => PetPolicy::isGlobalAdmin(Filament::auth()->user()))
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
                Filter::make('pets_info_filters')
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make('Pets Information')
                            ->columns(3)
                            ->schema([
                                Select::make('pet_type')
                                    ->label(__('app.type'))
                                    ->options(PetType::options())
                                    ->searchable()
                                    ->native(false),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(! empty($data['pet_type']), fn (Builder $q) => $q->where('type', $data['pet_type']));
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(2)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    ViewAction::make(),
                    DeleteAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                FilamentExportBulkAction::make('export')
                    ->fileName('Pet-Report')
                    ->disableAdditionalColumns()
                    ->disableCsv()
                    ->disablePdf()
                    ->disableFilterColumns()
                    ->action(function (Component $livewire) {
                        $columns = FilamentTableExportSupport::visibleColumnNames($livewire);
                        $fileNameInput = $livewire->mountedActions[0]['data']['file_name'] ?? null;
                        $fileName = FilamentTableExportSupport::excelFileName($fileNameInput, 'Pet-Report');
                        $ids = FilamentTableExportSupport::selectedRecordIds($livewire);
                        $pets = Pet::whereIn('id', $ids, 'and', false)->latest('id')->get();

                        FilamentTableExportSupport::recordExportAudit(Auth::user(), 'exported pet records');

                        return Excel::download(new PetExport($pets, $columns), $fileName);
                    }),
                DeleteBulkAction::make(),
            ]);
    }
}
