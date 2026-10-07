<?php

namespace App\Filament\Resources\PrivateClaimSuppliers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PrivateClaimSuppliersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('private_claim_category_title_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('company_name')
                    ->searchable(),
                TextColumn::make('company_name_th')
                    ->searchable(),
                TextColumn::make('brand')
                    ->searchable(),
                TextColumn::make('contact_person_name')
                    ->searchable(),
                TextColumn::make('contact_person_mobile_no')
                    ->searchable(),
                TextColumn::make('contact_person_email')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('app.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('app.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
