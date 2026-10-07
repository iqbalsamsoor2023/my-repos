<?php

namespace App\Filament\Resources\LogisticPartners\Tables;

use App\Enums\LogisticPartner\ModesType;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class LogisticPartnersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('logo')
                    ->collection('default')
                    ->toggleable(),
                TextColumn::make('modes')
                    ->label(__('parcel.modes'))
                    ->formatStateUsing(fn(?int $state) => ModesType::tryFrom($state)?->getLabel())
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('app.name'))
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns);
    }
}
