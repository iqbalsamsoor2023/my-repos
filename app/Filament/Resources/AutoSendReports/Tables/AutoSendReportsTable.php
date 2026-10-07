<?php

namespace App\Filament\Resources\AutoSendReports\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AutoSendReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('residence.name')
                    ->label(__('app.mooban_or_residence'))
                    ->hidden(auth()->user()->hasRole(['Property Management'])),
                TextColumn::make('email')
                    ->label(__('app.email')),
                TextColumn::make('time')
                    ->label(__('app.time')),
                TextColumn::make('hour')
                    ->label(__('app.hour')),
                TextColumn::make('module_type')
                    ->label(__('app.module_type')),
                TextColumn::make('created_at')
                    ->label(__('app.created_at'))
                    ->getStateUsing(function (Model $record) {
                        return date('d-M-y H:i:s', strtotime($record->created_at));
                    }),
                TextColumn::make('updated_at')
                    ->label(__('app.updated_at'))
                    ->getStateUsing(function (Model $record) {
                        return date('d-M-y H:i:s', strtotime($record->updated_at));
                    }),
                TextColumn::make('deleted_at')
                    ->label(__('app.deleted_at'))
                    ->getStateUsing(function (Model $record) {
                        if (is_null($record->deleted_at) == false) {
                            return date('d-M-y H:i:s', strtotime($record->deleted_at));
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('email')
                    ->schema([
                        TextInput::make('email')
                            ->label(__('app.email')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['email'])) {
                            return $query->where('email', 'LIKE', '%'.$data['email'].'%');
                        }
                    }),
                SelectFilter::make('residence')
                    ->label(__('app.mooban_or_residence'))
                    ->options(list_residences())
                    ->searchable()
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn (Builder $query): Builder => $query->where('residence_id', $data['value']),
                            );
                    }),
                SelectFilter::make('module_type')
                    ->label(__('app.module_type'))
                    ->options([
                        'Visitor' => 'Visitor',
                        'PGS' => 'PGS',
                        'IRS' => 'IRS',
                    ]),
            ],layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->hidden(auth()->user()->hasRole(['Admin'])),
                ForceDeleteBulkAction::make()
                    ->hidden(auth()->user()->hasRole(['Admin'])),
                RestoreBulkAction::make()
                    ->hidden(auth()->user()->hasRole(['Admin'])),
            ]);
    }
}
