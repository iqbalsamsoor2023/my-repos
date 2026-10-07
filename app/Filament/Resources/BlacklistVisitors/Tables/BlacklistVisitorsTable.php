<?php

namespace App\Filament\Resources\BlacklistVisitors\Tables;

use App\Enums\Visitor\IdType;
use App\Filament\Resources\Vms\RelationManagers\BlacklistedVisitorsRelationManager;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Livewire\Component;

class BlacklistVisitorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('residence.name')
                    ->label(__('app.mooban_or_residence'))
                    ->searchable()
                    ->hidden(fn (Component $livewire): bool => $livewire instanceof BlacklistedVisitorsRelationManager),
                TextColumn::make('visitor.name')
                    ->label(__('app.name'))
                    ->searchable(),
                TextColumn::make('visitor.id_type')
                    ->label(__('Id type'))
                    ->formatStateUsing(function (string $state): string {
                        $idTypeMappings = [
                            IdType::IC->value => IdType::IC->name,
                            IdType::PASSPORT->value => ucfirst(strtolower(IdType::PASSPORT->name)),
                            IdType::DRIVING_LICENSE->value => str_replace('_', ' ', ucfirst(strtolower(IdType::DRIVING_LICENSE->name))),
                        ];

                        $idType = $idTypeMappings[$state] ?? '-';

                        return $idType;
                    }),
                TextColumn::make('visitor.id_number')
                    ->label(__('ID Number'))
                    ->searchable(),
                TextColumn::make('vehicle_plate_no')
                    ->label(__('Vehicle Plate Number'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('app.created_at'))
                    ->dateTime(),
                TextColumn::make('updated_at')
                    ->label(__('app.updated_at'))
                    ->dateTime(),
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
