<?php

namespace App\Filament\Resources\Announcements\Tables;

use App\Filament\Resources\Announcements\Pages\ListAnnouncements;
use App\Models\Announcement;
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

class AnnouncementsTable
{
    public static function configure(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->columns([
                TextColumn::make('residence.name')
                    ->label(__('app.mooban_or_residence'))
                    ->toggleable()
                    ->description(fn (Announcement $record): string => $record?->residence?->name_th ?? '-')
                    ->hidden(auth()->user()->hasRole(['Property Management'])),
                TextColumn::make('title')
                    ->label(__('app.title'))
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('description')
                    ->label(__('app.description'))
                    ->limit(50)
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label(__('app.is_active'))
                    ->boolean()
                    ->toggleable(),
                ColumnGroup::make(__('app.created_at'), [
                    TextColumn::make('created_at_date')
                        ->label(__('app.date'))
                        ->getStateUsing(function (Announcement $record) {
                            return $record->created_at->format('d-M-y');
                        }),
                    TextColumn::make('created_at_time')
                        ->label(__('app.time'))
                        ->getStateUsing(function (Announcement $record) {
                            return $record->created_at->format('H:i:s');
                        }),
                ]),
                ColumnGroup::make(__('app.updated_at'), [
                    TextColumn::make('updated_at_date')
                        ->label(__('app.date'))
                        ->getStateUsing(function (Announcement $record) {
                            return $record->updated_at->format('d-M-y');
                        }),
                    TextColumn::make('updated_at_time')
                        ->label(__('app.time'))
                        ->getStateUsing(function (Announcement $record) {
                            return $record->updated_at->format('H:i:s');
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('province')
                    ->label(__('app.province'))
                    ->options(list_provinces())
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn (Builder $query): Builder => $query->whereHas('residence.subdistrict.district.province', function ($q) use ($data) {
                                    return $q->whereId($data['value']);
                                }),
                            );
                    })
                    ->visible(fn (Component $livewire): bool => $livewire instanceof ListAnnouncements && $user->hasRole(['Super Admin', 'Admin', 'Property Management Operation Center'])),
                SelectFilter::make('residence')
                    ->label(__('app.mooban_or_residence'))
                    ->options(list_residences())
                    ->searchable()
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn (Builder $query): Builder => $query->whereHas('residence', function ($q) use ($data) {
                                    return $q->whereId($data['value']);
                                }),
                            );
                    })
                    ->visible(fn (Component $livewire): bool => $livewire instanceof ListAnnouncements && $user->hasRole(['Super Admin', 'Admin', 'Property Management Operation Center'])),
                // Tables\Filters\SelectFilter::make('district')
                //     ->options(isset($_REQUEST['tableFilters']['province']['value']) ? ThailandDistrict::where('province_id', $_REQUEST['tableFilters']['province']['value'])->pluck('name_in_english', 'id') : [])
                //     ->query(function (Builder $query, array $data): Builder {
                //         return $query
                //             ->when(
                //                 $data['value'],
                //                 fn (Builder $query): Builder => $query->whereHas('residence.subdistrict.district', function ($q) use ($data) {
                //                     return $q->whereId($data['value']);
                //                 }),
                //             );
                //     }),
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
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                        }
                    }),
            ],layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(2)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    ViewAction::make()
                        ->visible($user->hasAnyRole(['Admin', 'Property Management Operation Center'])),
                    DeleteAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->hidden($user->hasAnyRole(['Admin'])),
            ]);
    }
}
