<?php

namespace App\Filament\Resources\Events\Tables;

use App\Filament\Resources\Events\Pages\ListEvents;
use App\Models\Erp\ThailandProvince;
use App\Models\Event;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->columns([
                TextColumn::make('residence.name')
                    ->label(__('app.mooban_or_residence'))
                    ->toggleable()
                    ->description(fn(Event $record): string => $record?->residence?->name_th ?? '-')
                    ->hidden($user->hasRole(['Property Management'])),
                TextColumn::make('title')
                    ->label(__('app.title'))
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('description')
                    ->label(__('app.description'))
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('start_at')
                    ->label(__('app.start_at'))
                    ->toggleable()
                    ->getStateUsing(function (Event $record) {
                        return date('d-M-y H:i:s', strtotime($record->start_at));
                    }),
                TextColumn::make('end_at')
                    ->label(__('app.end_at'))
                    ->toggleable()
                    ->getStateUsing(function (Event $record) {
                        return date('d-M-y H:i:s', strtotime($record->end_at));
                    }),
                TextColumn::make('event_duration')
                    ->label(__('app.duration'))
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label(__('app.is_active'))
                    ->boolean()
                    ->toggleable(),
                ColumnGroup::make(__('app.created_at'), [
                    TextColumn::make('created_at_date')
                        ->label(__('app.date'))
                        ->getStateUsing(function (Event $record) {
                            return $record->created_at->format('d-M-y');
                        }),
                    TextColumn::make('created_at_time')
                        ->label(__('app.time'))
                        ->getStateUsing(function (Event $record) {
                            return $record->created_at->format('H:i:s');
                        }),
                ]),
                ColumnGroup::make(__('app.updated_at'), [
                    TextColumn::make('updated_at_date')
                        ->label(__('app.date'))
                        ->getStateUsing(function (Event $record) {
                            return $record->updated_at->format('d-M-y');
                        }),
                    TextColumn::make('updated_at_time')
                        ->label(__('app.time'))
                        ->getStateUsing(function (Event $record) {
                            return $record->updated_at->format('H:i:s');
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('province')
                    ->label(__('app.province'))
                    ->options(ThailandProvince::get()->pluck('name_in_english', 'id'))
                    ->searchable()
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn(Builder $query): Builder => $query->whereHas('residence.subdistrict.district.province', function ($q) use ($data) {
                                    return $q->whereId($data['value']);
                                }),
                            );
                    })
                    ->visible(fn(Component $livewire): bool => $livewire instanceof ListEvents && $user->hasRole(['Super Admin', 'Admin', 'Property Management Operation Center'])),
                SelectFilter::make('residence')
                    ->label(__('app.mooban_or_residence'))
                    ->relationship(
                        name: 'residence',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => applyResidenceRoleFilter($query)
                    )
                    ->searchable()
                    ->getOptionLabelFromRecordUsing(
                        fn ($record) => "{$record->name} ({$record->name_th})"
                    )
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn(Builder $query): Builder => $query->whereHas('residence', function ($q) use ($data) {
                                    return $q->whereId($data['value']);
                                }),
                            );
                    })
                    ->visible(fn(Component $livewire): bool => $livewire instanceof ListEvents && $user->hasRole(['Super Admin', 'Admin', 'Property Management Operation Center'])),
                TernaryFilter::make('is_active')->label(__('app.is_active')),
                Filter::make('start_at')
                    ->schema([
                        DatePicker::make('start_from')
                            ->label(__('app.start_from')),
                        DatePicker::make('start_until')
                            ->label(__('app.start_until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('start_at', '>=', $date),
                            )
                            ->when(
                                $data['start_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('start_at', '<=', $date),
                            );
                    }),
                Filter::make('end_at')
                    ->schema([
                        DatePicker::make('end_from')
                            ->label(__('app.end_from')),
                        DatePicker::make('end_until')
                            ->label(__('app.end_until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['end_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('end_at', '>=', $date),
                            )
                            ->when(
                                $data['end_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('end_at', '<=', $date),
                            );
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
