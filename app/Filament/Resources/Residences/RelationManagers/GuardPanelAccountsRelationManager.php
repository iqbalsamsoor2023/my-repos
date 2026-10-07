<?php

namespace App\Filament\Resources\Residences\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GuardPanelAccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'guardPanelAccounts';

    protected static ?string $recordTitleAttribute = 'user_id';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('digitalTool.asset_code')
                    ->label(__('app.digital_device')),
                TextColumn::make('sim.number')
                    ->label(__('app.sim_number')),
                TextColumn::make('email')
                    ->label(__('app.email'))
                    ->copyable()
                    ->badge()
                    ->getStateUsing(function ($record) {
                        return $record->user_platform == 'sgoc' && $record->sgocUser ? $record->sgocUser->email : null;
                    }),
                IconColumn::make('service_end_date')
                    ->label(__('app.is_active'))
                    ->boolean()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        return $record->service_end_date == null;
                    })
                    ->trueIcon('heroicon-o-check')
                    ->falseIcon('heroicon-o-x-mark'),
            ]);
    }
}
