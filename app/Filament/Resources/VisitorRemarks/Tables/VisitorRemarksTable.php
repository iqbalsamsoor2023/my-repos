<?php

namespace App\Filament\Resources\VisitorRemarks\Tables;

use App\Filament\Resources\Vms\RelationManagers\VisitorRemarksRelationManager;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Livewire\Component;

class VisitorRemarksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('residence.name')
                    ->label(__('app.mooban_or_residence'))
                    ->searchable()
                    ->hidden(fn (Component $livewire): bool => $livewire instanceof VisitorRemarksRelationManager),
                TextColumn::make('remark')
                    ->translateLabel()
                    ->limit(150)
                    ->searchable(),
            ])
            ->defaultSort('updated_at', 'desc')
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
