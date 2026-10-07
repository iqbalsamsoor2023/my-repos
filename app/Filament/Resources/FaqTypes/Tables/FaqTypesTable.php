<?php

namespace App\Filament\Resources\FaqTypes\Tables;

use App\Models\Erp\FaqType;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class FaqTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('platform.name')
                    ->label(__('app.platform'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('name')
                    ->label(__('app.faq_type'))
                    ->description(fn (FaqType $record): string => $record->name_in_thai ?? '-')
                    ->html()
                    ->wrap()
                    ->words(30)
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label(__('app.is_active'))
                    ->boolean(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
