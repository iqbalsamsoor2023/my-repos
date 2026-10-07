<?php

namespace App\Filament\Resources\ClaimableTitles\Tables;

use App\Enums\FacilityAndAmenity\AmenityTypeEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ClaimableTitlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category')
                    ->label(__('support-ticket.category')),
                TextColumn::make('name')
                    ->label(__('app.name'))
                    ->description(fn (Model $record): string => $record->name_in_thai ?? '-')
                    ->copyable()
                    ->searchable(query: function ($query, $search) {
                        $query->where('name', 'like', "%{$search}%")
                              ->orWhere('name_in_thai', 'like', "%{$search}%");
                    }),
                TextColumn::make('type')
                    ->label(__('app.type'))
                    ->formatStateUsing(fn (?int $state) => AmenityTypeEnum::tryFrom($state)?->label())
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
