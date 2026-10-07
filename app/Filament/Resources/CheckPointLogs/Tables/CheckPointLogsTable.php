<?php

namespace App\Filament\Resources\CheckPointLogs\Tables;

use App\Enums\Checkpoint\CheckpointLogStatus;
use App\Enums\User\RoleType;
use App\Models\CheckpointLog;
use App\Models\Residence;
use Carbon\Carbon;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CheckPointLogsTable
{
    public static function configure(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->columns([
                TextColumn::make('checkpoint.residence.name')
                    ->label(__('app.mooban_or_residence'))
                    ->toggleable()
                    ->description(fn(CheckpointLog $record): string => $record?->checkpoint?->residence?->name_th ?? '-')
                    ->visible($user->hasRole([RoleType::SUPER_ADMIN->value, RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value, RoleType::DEVELOPER->value])),
                TextColumn::make('user.name')
                    ->label(__('checkpoint.checked_by'))
                    ->toggleable(),
                TextColumn::make('checkpointRound.round')
                    ->label(__('checkpoint.round'))
                    ->formatStateUsing(function ($state, CheckpointLog $record) {
                        if ($record->checkpointRound?->round) {
                            $round = $record->checkpointRound->round;

                            return $round->round_number . ' (' . Carbon::parse($round->start_time)->format('H:i') . ' - ' . Carbon::parse($round->end_time)->format('H:i') . ')';
                        }

                        return '-';
                    })
                    ->toggleable(),
                TextColumn::make('checkpoint.zone.zone_number')
                    ->label(__('checkpoint.zone'))
                    ->toggleable(),
                TextColumn::make('checkpointRound.sequence')
                    ->label(__('checkpoint.checkpoint_sequence'))
                    ->toggleable(),
                TextColumn::make('checkpoint.name')
                    ->label(__('checkpoint.checkpoint_name'))
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('checkpoint.status'))
                    ->badge()
                    ->toggleable(),
                SpatieMediaLibraryImageColumn::make('image')
                    ->label(__('app.image'))
                    ->collection('default')
                    ->toggleable(),
                TextColumn::make('checkpoint')
                    ->formatStateUsing(function ($state, CheckpointLog $record) {
                        if ($record->checkpoint?->is_outdoor) {
                            return $record->checkpoint?->latitude . ', ' . $record->checkpoint?->longitude;
                        }

                        return '-';
                    })
                    ->label(__('checkpoint.location'))
                    ->toggleable(),
                TextColumn::make('remark')
                    ->label(__('app.remark'))
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('app.created_at'))
                    ->dateTime()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('residences')
                    ->schema([
                        Select::make('residence')
                            ->label(__('app.mooban_or_residence'))
                            // ->relationship(
                            //     name: 'checkpoint.residence',
                            //     titleAttribute: 'name',
                            //     modifyQueryUsing: fn($query) => applyResidenceRoleFilter($query)
                            // )
                            // ->preload()
                            ->options(function () use ($user) {
                                return Residence::query()
                                    ->when(
                                        ! $user->hasRole([
                                            'Super Admin',
                                            'Property Management Operation Center',
                                            'Developer',
                                        ]),
                                        fn ($query) => applyResidenceRoleFilter($query)
                                    )
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn ($record) => [
                                        $record->id => "{$record->name} ({$record->name_th})",
                                    ])
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload(false)
                            ->getOptionLabelFromRecordUsing(
                                fn($record) => "{$record->name} ({$record->name_th})"
                            )
                            ->searchable()
                            ->visible($user->hasRole([RoleType::SUPER_ADMIN->value, RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value, RoleType::DEVELOPER->value])),
                        Select::make('checkpoint')
                            ->label(__('checkpoint.checkpoint'))
                            ->hint($user->hasRole([RoleType::SUPER_ADMIN->value, RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value, RoleType::DEVELOPER->value])
                                ? __('checkpoint.please_select_residence_first')
                                : null)
                            ->hintColor('gray')
                            ->options(function (callable $get) use ($user) {
                                if ($user->hasRole(RoleType::PROPERTY_MANAGEMENT->value)) {
                                    return list_checkpoints($user->propertyManagement->id);
                                } else {
                                    return list_checkpoints($get('residence'));
                                }
                            })
                            ->searchable(true),
                        Select::make('round')
                            ->label(__('checkpoint.round'))
                            ->options(function (callable $get) use ($user) {
                                if ($user->hasRole(RoleType::PROPERTY_MANAGEMENT->value)) {
                                    return list_rounds($user->propertyManagement->id);
                                } else {
                                    return list_rounds($get('residence'));
                                }
                            })
                            ->searchable(true),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['residence'],
                                fn(Builder $query): Builder => $query->whereHas('checkpoint.residence', function ($q) use ($data) {
                                    return $q->whereId($data['residence']);
                                }),
                            )
                            ->when(
                                $data['checkpoint'],
                                fn(Builder $query): Builder => $query->whereHas('checkpoint', function ($q) use ($data) {
                                    return $q->whereId($data['checkpoint']);
                                }),
                            )
                            ->when(
                                $data['round'],
                                fn(Builder $query): Builder => $query->whereHas('checkpointRound.round', function ($q) use ($data) {
                                    return $q->whereId($data['round']);
                                }),
                            );
                    }),
                Filter::make('name')
                    ->schema([
                        TextInput::make('name')
                            ->label(__('user.security_guard_staff_name')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['name'])) {
                            return $query->whereHas('user', function ($q) use ($data) {
                                return $q->where('name', 'LIKE', '%' . $data['name'] . '%');
                            });
                        }
                    }),
                Filter::make('status')
                    ->schema([
                        Select::make('status')
                            ->label(__('app.status'))
                            ->options(collect(CheckpointLogStatus::cases())->mapWithKeys(
                                fn(CheckpointLogStatus $case) => [$case->value => $case->getLabel()]
                            )->toArray())
                            ->multiple()
                            ->searchable(),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['status'])) {
                            $query->whereIn('status', $data['status']);
                        }
                    })
                    ->indicateUsing(function (array $data): array {
                        if (empty($data['status'])) {
                            return [];
                        }
                        return [
                            __('app.status') . ': ' . collect($data['status'])
                                ->map(fn($val) => CheckpointLogStatus::from((int) $val)->getLabel())
                                ->join(', '),
                        ];
                    }),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label(__('app.created_from'))
                            ->default(Carbon::now()->toDateString()),

                        DatePicker::make('created_until')
                            ->label(__('app.created_until'))
                            ->default(Carbon::now()->toDateString()),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['created_from']) && isset($data['created_until'])) {
                            return $query
                                ->when(
                                    $data['created_from'],
                                    fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                                )
                                ->when(
                                    $data['created_until'],
                                    fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                                );
                        }
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $indicator = null;
                        if (isset($data['created_from'])) {
                            $indicator = 'From Date: ' . Carbon::parse($data['created_from'])->format('M d, Y');
                        }

                        if (isset($data['created_until'])) {
                            if ($indicator) {
                                $indicator .= ' ';
                            }
                            $indicator .= 'To Date: ' . Carbon::parse($data['created_until'])->format('M d, Y');
                        }

                        return $indicator;
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->defaultSort('id', 'desc')
            ->paginationPageOptions([10]);
    }
}
