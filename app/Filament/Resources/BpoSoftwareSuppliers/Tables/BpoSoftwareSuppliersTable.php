<?php

namespace App\Filament\Resources\BpoSoftwareSuppliers\Tables;

use App\Enums\Residence\BpoSoftwareEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class BpoSoftwareSuppliersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('bpo_software.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name_th')
                    ->label(__('bpo_software.name_th'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->label(__('bpo_software.category'))
                    ->formatStateUsing(fn(string $state): string => BpoSoftwareEnum::from($state)->label())
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('bpo_software.created_at'))
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('bpo_software.updated_at'))
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('category')
                    ->label(__('bpo_software.category'))
                    ->options(BpoSoftwareEnum::options()),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                    RestoreAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
