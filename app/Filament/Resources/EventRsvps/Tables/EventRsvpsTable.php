<?php

namespace App\Filament\Resources\EventRsvps\Tables;

use App\Models\EventRsvp;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class EventRsvpsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('app.name')),
                TextColumn::make('user')
                    ->label(__('unit.unit_number'))
                    ->formatStateUsing(function ($record) {
                        $eventResidenceId = $record->event->residence_id;

                        $units = $record->user->units()
                            ->where('residence_id', $eventResidenceId)
                            ->pluck('unit_number')
                            ->toArray();

                        return implode(', ', $units);
                    }),
                IconColumn::make('is_going')
                    ->label(__('app.is_going'))
                    ->boolean(),
                ColumnGroup::make(__('app.created_at'), [
                    TextColumn::make('created_at_date')
                        ->label(__('app.date'))
                        ->getStateUsing(function (EventRsvp $record) {
                            return date('d-M-y', strtotime($record->created_at));
                        }),
                    TextColumn::make('created_at_time')
                        ->label(__('app.time'))
                        ->getStateUsing(function (EventRsvp $record) {
                            return date('H:i:s', strtotime($record->created_at));
                        }),
                ]),
                ColumnGroup::make(__('app.updated_at'), [
                    TextColumn::make('updated_at_date')
                        ->label(__('app.date'))
                        ->getStateUsing(function (EventRsvp $record) {
                            return date('d-M-y', strtotime($record->updated_at));
                        }),
                    TextColumn::make('updated_at_time')
                        ->label(__('app.time'))
                        ->getStateUsing(function (EventRsvp $record) {
                            return date('H:i:s', strtotime($record->updated_at));
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_going'),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->headerActions([
                CreateAction::make()
                    ->modalHeading(__('menu.create_rsvp'))
                    ->label(__('menu.new_rsvp')),
            ]);
    }
}
