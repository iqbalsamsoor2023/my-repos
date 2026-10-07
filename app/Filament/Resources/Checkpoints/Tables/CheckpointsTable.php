<?php

namespace App\Filament\Resources\Checkpoints\Tables;

use App\Models\Sgoc\Checkpoint;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CheckpointsTable
{
    public static function configure(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->columns([
                TextColumn::make('residence.name')
                    ->label(__('app.mooban_or_residence'))
                    ->toggleable()
                    ->description(fn(Checkpoint $record): string => $record?->residence?->name_th ?? '-')
                    ->visible($user->hasRole(['Super Admin', 'Admin', 'Property Management Operation Center', 'Developer'])),
                TextColumn::make('name')
                    ->label(__('checkpoint.location'))
                    ->toggleable(),
                TextColumn::make('description')
                    ->label(__('app.description'))
                    ->wrap()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label(__('app.is_active'))
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('app.created_at_date'))
                    ->getStateUsing(function (Checkpoint $record) {
                        return $record->created_at->format('d-M-y');
                    }),
                TextColumn::make('created_at_time')
                    ->label(__('app.created_at_time'))
                    ->getStateUsing(function (Checkpoint $record) {
                        return $record->created_at->format('H:i:s');
                    }),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('residence')
                    ->label(__('app.mooban_or_residence'))
                    ->options(list_residences())
                    ->searchable()
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['value'])) {
                            return $query
                                ->when(
                                    $data['value'],
                                    fn(Builder $query): Builder => $query->whereHas('residence', function ($q) use ($data) {
                                        return $q->whereId($data['value']);
                                    }),
                                );
                        }
                    })
                    ->visible($user->hasRole(['Super Admin', 'Admin', 'Property Management Operation Center', 'Developer'])),
                Filter::make('location')
                    ->schema([
                        TextInput::make('location')
                            ->label(__('checkpoint.location')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['location'])) {
                            return $query->where('name', 'LIKE', '%' . $data['location'] . '%');
                        }
                    }),
                TernaryFilter::make('is_active')
                    ->label(__('app.is_active')),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label(__('app.created_from')),
                        DatePicker::make('created_until')
                            ->label(__('app.created_until')),
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
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(2)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
