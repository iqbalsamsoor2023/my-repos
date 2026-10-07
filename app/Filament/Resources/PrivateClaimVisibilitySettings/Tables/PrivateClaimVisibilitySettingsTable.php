<?php

namespace App\Filament\Resources\PrivateClaimVisibilitySettings\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class PrivateClaimVisibilitySettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('app.residence'))
                    ->searchable()
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->url(fn ($record) => \App\Filament\Resources\PrivateClaimVisibilitySettings\PrivateClaimVisibilitySettingResource::getUrl('edit', [
                            'record' => $record,
                        ])),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                // BulkActionGroup::make([
                //     DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
