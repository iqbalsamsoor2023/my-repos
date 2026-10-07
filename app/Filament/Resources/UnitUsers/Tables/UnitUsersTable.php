<?php

namespace App\Filament\Resources\UnitUsers\Tables;

use App\Enums\GeneralStatus;
use App\Enums\Residence\MoobanType;
use App\Enums\UnitUser\ApprovalStatusType;
use App\Enums\User\Gender;
use App\Exports\UnitUserExport;
use App\Mail\UserRegistered;
use App\Models\Country;
use App\Models\ResidenceActivationStatus;
use App\Models\UnitUser;
use App\Models\UnitUserStatsView;
use App\Policies\UnitUserPolicy;
use App\Services\FilamentExport\FilamentExportBulkAction;
use App\Services\FilamentExport\FilamentTableExportSupport;
use App\Services\ResidenceService;
use App\Services\ThailandLocationService;
use App\Support\QueryGuardSupport;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class UnitUsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('unit.residence.name')
                    ->label(__('app.mooban_or_residence'))
                    ->copyable()
                    ->toggleable()
                    ->description(fn (UnitUser $record): string => $record?->unit?->residence?->name_th ?? '-'),
                ViewColumn::make('unit_detail')
                    ->label(__('unit.unit'))
                    ->view('tables.columns.unit-user.unit-detail')
                    ->toggleable(),
                ViewColumn::make('resident_detail')
                    ->label(__('app.resident'))
                    ->view('tables.columns.unit-user.resident-detail')
                    ->toggleable(),
                TextColumn::make('mmb_id')
                    ->label(__('app.mmb_id'))
                    ->copyable()
                    ->toggleable(),
                IconColumn::make('is_owner')
                    ->label(__('user.is_owner'))
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('is_main_owner')
                    ->label(__('user.is_main_owner'))
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('is_main_tenant')
                    ->label(__('user.is_main_tenant'))
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('relationship')
                    ->label(__('user.relationship'))
                    ->toggleable(),
                TextColumn::make('approval_status')
                    ->label(__('user.approval_status'))
                    ->formatStateUsing(function (string $state): string {
                        $approvalStatuses = [
                            ApprovalStatusType::APPROVED->value => __('app.'.strtolower(ApprovalStatusType::APPROVED->name)),
                            ApprovalStatusType::REJECTED->value => __('app.'.strtolower(ApprovalStatusType::REJECTED->name)),
                        ];

                        $approvalStatus = $approvalStatuses[$state] ?? '-';

                        return __($approvalStatus);
                    })
                    ->toggleable(),
                TextColumn::make('mail_status')
                    ->label(__('user.email_status'))
                    ->formatStateUsing(function (string $state): string {
                        return $state == GeneralStatus::INACTIVE->value ? __('Pending') : __('Verified');
                    })
                    ->getStateUsing(fn (UnitUser $record) => ($record->user->email_verified_at ?? GeneralStatus::INACTIVE->value) ? GeneralStatus::ACTIVE->value : GeneralStatus::INACTIVE->value)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('app.created_at_date'))
                    ->toggleable()
                    ->getStateUsing(function (UnitUser $record) {
                        return $record->created_at->format('d-M-y');
                    }),
                TextColumn::make('created_at_time')
                    ->label(__('app.created_at_time'))
                    ->toggleable()
                    ->getStateUsing(function (UnitUser $record) {
                        return $record->created_at->format('H:i:s');
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(10)
            ->filters([
                Filter::make('mooban')
                    ->visible(fn () => UnitUserPolicy::isGlobalAdmin(Filament::auth()->user()))
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
                                    ->searchable()
                                    ->optionsLimit(50)
                                    ->getSearchResultsUsing(fn (string $search): array => ResidenceService::searchResidencesForUser($search))
                                    ->getOptionLabelUsing(fn ($value): ?string => ResidenceService::getResidenceLabelForUser($value)),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return static::applyStatsViewPrefilter($query, function ($subQuery) use ($data) {
                            if (! empty($data['mooban_type'])) {
                                $subQuery->whereIn('mooban_type', (array) $data['mooban_type']);
                            }

                            if (! empty($data['sub_type'])) {
                                $subQuery->whereIn('sub_type', (array) $data['sub_type']);
                            }

                            if (! empty($data['residence_id'])) {
                                $subQuery->where('residence_id', $data['residence_id']);
                            }
                        });
                    }),
                Filter::make('resident_filters')
                    ->label(__('app.resident'))
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make('Unit & Email')
                            ->columns(2)
                            ->schema([
                                TextInput::make('unit')
                                    ->label(__('unit.unit_number')),
                                TextInput::make('email')
                                    ->label(__('app.email')),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return static::applyStatsViewPrefilter($query, function ($subQuery) use ($data) {
                            if (! empty($data['unit'])) {
                                $subQuery->where('unit_number', 'LIKE', '%'.$data['unit'].'%');
                            }

                            if (! empty($data['email'])) {
                                $subQuery->where('email', 'LIKE', '%'.$data['email'].'%');
                            }
                        });
                    }),
                Filter::make('province_filters')
                    ->visible(fn () => UnitUserPolicy::isGlobalAdmin(Filament::auth()->user()))
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
                                        $provinceIds = array_map('intval', array_filter((array) $get('province')));

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
                                        $districtIds = array_map('intval', array_filter((array) $get('district')));

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
                                    ->native(false)
                                    ->live(),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $provinceIds = array_map('intval', array_filter((array) ($data['province'] ?? [])));
                        $districtIds = array_map('intval', array_filter((array) ($data['district'] ?? [])));
                        $subdistrictIds = array_map('intval', array_filter((array) ($data['subdistrict'] ?? [])));
                        $residenceIds = ThailandLocationService::getResidenceIdsByLocation($provinceIds, $districtIds, $subdistrictIds);
                        $hasLocationFilters = ! empty($provinceIds) || ! empty($districtIds) || ! empty($subdistrictIds);

                        return static::applyStatsViewPrefilter($query, function ($subQuery) use ($data, $residenceIds, $hasLocationFilters) {
                            if ($hasLocationFilters) {
                                QueryGuardSupport::whereInOrDenyAll($subQuery, 'residence_id', $residenceIds);
                            }

                            if (! empty($data['main_road'])) {
                                $subQuery->where('main_road', 'LIKE', '%'.$data['main_road'].'%');
                            }
                        });
                    }),
                Filter::make('status_filters')
                    ->label(__('app.status'))
                    ->visible(fn () => UnitUserPolicy::isGlobalAdmin(Filament::auth()->user()))
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
                        return static::applyStatsViewPrefilter($query, function ($subQuery) use ($data) {
                            if (! empty($data['residence_activation_status_id'])) {
                                $subQuery->whereIn('residence_activation_status_id', (array) $data['residence_activation_status_id']);
                            }
                        });
                    }),
                Filter::make('date_filters')
                    ->label(__('app.dates_filtering'))
                    ->visible(fn () => UnitUserPolicy::isGlobalAdmin(Filament::auth()->user()))
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
                        return static::applyStatsViewPrefilter($query, function ($subQuery) use ($data) {
                            if (! empty($data['created_from'])) {
                                $subQuery->whereDate('unit_user_created_at', '>=', $data['created_from']);
                            }

                            if (! empty($data['created_until'])) {
                                $subQuery->whereDate('unit_user_created_at', '<=', $data['created_until']);
                            }

                            if (! empty($data['updated_from'])) {
                                $subQuery->whereDate('unit_user_updated_at', '>=', $data['updated_from']);
                            }

                            if (! empty($data['updated_until'])) {
                                $subQuery->whereDate('unit_user_updated_at', '<=', $data['updated_until']);
                            }
                        });
                    }),

                // User Demographics Filter
                Filter::make('user_demographics')
                    ->label(__('app.user_demographics'))
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make('Demographics')
                            ->columns(3)
                            ->schema([
                                Select::make('country_id')
                                    ->label(__('user.nationality'))
                                    ->searchable()
                                    ->optionsLimit(50)
                                    ->getSearchResultsUsing(fn (string $search): array => Country::query()
                                        ->where('name', 'LIKE', "%{$search}%")
                                        ->orderBy('name', 'asc')
                                        ->limit(50)
                                        ->pluck('name', 'id')
                                        ->toArray())
                                    ->getOptionLabelUsing(fn ($value): ?string => Country::query()->whereKey($value)->value('name'))
                                    ->multiple(),
                                Select::make('gender')
                                    ->label(__('user.gender'))
                                    ->options(function () {
                                        $cases = collect(Gender::cases())
                                            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                                            ->toArray();

                                        return ['not_set' => __('user.not_yet_set')] + $cases;
                                    })
                                    ->multiple(),
                                Select::make('age_group')
                                    ->label(__('Age Group'))
                                    ->options([
                                        '06-12' => '06-12 years',
                                        '13-22' => '13-22 years',
                                        '23-30' => '23-30 years',
                                        '31-40' => '31-40 years',
                                        '41-50' => '41-50 years',
                                        '51-60' => '51-60 years',
                                        '61-70' => '61-70 years',
                                        '>70' => '>70 years',
                                        'Not Set' => 'Not Set',
                                    ]),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return static::applyStatsViewPrefilter($query, function ($subQuery) use ($data) {
                            if (! empty($data['country_id'])) {
                                $subQuery->whereIn('country_id', (array) $data['country_id']);
                            }

                            if (! empty($data['gender'])) {
                                $selected = (array) $data['gender'];
                                $includeNotSet = in_array('not_set', $selected, true);
                                $selected = array_values(array_filter($selected, fn ($v) => $v !== 'not_set'));

                                if ($includeNotSet && ! empty($selected)) {
                                    $subQuery->where(function ($q) use ($selected) {
                                        $q->whereNull('gender')->orWhereIn('gender', $selected);
                                    });
                                } elseif ($includeNotSet) {
                                    $subQuery->whereNull('gender');
                                } else {
                                    $subQuery->whereIn('gender', $selected);
                                }
                            }

                            if (! empty($data['age_group'])) {
                                $subQuery->whereIn('widget_age_group', (array) $data['age_group']);
                            }
                        });
                    }),
                TrashedFilter::make(),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    ViewAction::make(),
                    DeleteAction::make(),
                    RestoreAction::make()
                        ->visible(fn ($record) => $record->trashed()),
                    Action::make('Resend Verification')
                        ->icon('heroicon-o-envelope')
                        ->tooltip(function (UnitUser $record): string {
                            $email = data_get($record->user, 'email');

                            return "Resend verification mail at {$email}";
                        })
                        ->visible(fn (UnitUser $record): string => is_null(data_get($record->user, 'email_verified_at')) == true)
                        ->action(function (UnitUser $record) {
                            Mail::to($record->user->email)->send(new UserRegistered($record->user));
                        })
                        ->requiresConfirmation()
                        ->modalHeading(fn (UnitUser $record): string => "Resend verification mail at {$record->user->email}"),
                    Action::make('Verify Manually')
                        ->icon('heroicon-o-check-circle')
                        ->tooltip(fn (UnitUser $record): string => "Verify email manually for {$record->user->email}")
                        ->visible(fn (UnitUser $record): string => is_null($record->user->email_verified_at) == true)
                        ->action(function (UnitUser $record) {
                            $record->user()->update(['email_verified_at' => now()]);
                            // for v3 user email verification cache key for force verification
                            Cache::forget('user-app-new-user-'.$record->user->id);
                        }),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                DeleteBulkAction::make(),
                FilamentExportBulkAction::make('export')
                    ->fileName('Resident-Report')
                    ->disableAdditionalColumns()
                    ->disableCsv()
                    ->disablePdf()
                    ->disableFilterColumns()
                    ->action(function (array $data, Component $livewire) {
                        $columns = FilamentTableExportSupport::visibleColumnNames($livewire);
                        $fileName = FilamentTableExportSupport::excelFileName($data['file_name'] ?? null, 'Resident-Report');
                        $selectedResidentIds = FilamentTableExportSupport::selectedRecordIds($livewire);

                        $residents = UnitUser::whereIn('id', $selectedResidentIds, 'and', false)
                            ->latest('id')
                            ->get();

                        FilamentTableExportSupport::recordExportAudit(Filament::auth()->user(), 'exported resident records');

                        return Excel::download(new UnitUserExport($residents, $columns), $fileName);
                    }),
            ]);
    }

    private static function applyStatsViewPrefilter(Builder $query, \Closure $callback): Builder
    {
        $subquery = UnitUserStatsView::query()->select('unit_user_id');
        $callback($subquery);

        return $query->whereIn('unit_user.id', $subquery);
    }
}
