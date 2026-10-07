<?php

namespace App\Filament\Resources\ChatCategoryItems\Tables;

use App\Models\Audit;
use App\Models\Erp\ChatCategory;
use App\Models\Erp\ChatCategoryItem;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ChatCategoryItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('chatCategory.name')
                    ->label(__('support-ticket.category'))
                    ->description(fn (ChatCategoryItem $record): string => $record?->chatCategory?->name_in_thai ?? '-'),
                TextColumn::make('title')
                    ->label(__('app.title'))
                    ->description(fn (ChatCategoryItem $record): string => $record?->title_in_thai ?? '-'),
                ToggleColumn::make('is_active')
                    ->label(__('app.is_active')),
                TextColumn::make('created_at')
                    ->label(__('app.created_at_date'))
                    ->getStateUsing(function (ChatCategoryItem $record) {
                        return $record->created_at->format('d-M-y');
                    }),
                TextColumn::make('created_at_time')
                    ->label(__('app.created_at_time'))
                    ->getStateUsing(function (ChatCategoryItem $record) {
                        return $record->created_at->format('H:i:s');
                    }),
            ])
            ->defaultSort('chat_category_id', 'desc')
            ->filters([
                SelectFilter::make('category')
                    ->label(__('support-ticket.category'))
                    ->options(ChatCategory::whereKeyNot(5)->pluck('name', 'id')->mapWithKeys(function ($name, $id) {
                        $name_in_thai = optional(ChatCategory::find($id))->name_in_thai;
                        return [$id => $name . ' (' . $name_in_thai . ')'];
                    }))                             
                    ->searchable()
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn (Builder $query): Builder => $query->where('chat_category_id', $data['value']),
                            );
                    }),
                Filter::make('title')
                    ->schema([
                        TextInput::make('title')
                            ->label(__('app.title')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['title'],
                                fn (Builder $query): Builder => $query->where('title', 'LIKE', '%'.$data['title'].'%')->orWhere('title_in_thai', 'LIKE', '%'.$data['title'].'%'),
                            );
                    }),
            ],layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    ViewAction::make(),
                    DeleteAction::make()
                        ->before(function ($record) {
                            $oldValues = $record->only([
                                'id',
                                'name',
                                'source_id',
                                'platform_identifier',
                            ]);

                            $oldValues['created_at'] = optional($record->created_at)->format('Y-m-d H:i:s');
                            $oldValues['updated_at'] = optional($record->updated_at)->format('Y-m-d H:i:s');

                            Audit::create([
                                'user_type' => get_class(auth()->user()),
                                'user_id' => auth()->id(),
                                'event' => 'deleted',
                                'auditable_type' => get_class($record),
                                'auditable_id' => $record->id,
                                'old_values' => $oldValues,
                                'new_values' => null,
                                'url' => request()->fullUrl(),
                                'ip_address' => request()->ip(),
                                'user_agent' => request()->userAgent(),
                            ]);
                        }),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->hidden(auth()->user()->hasRole('Admin')),
            ]);
    }
}
