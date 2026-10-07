<?php

namespace App\Filament\Resources\Amenities\RelationManagers;

use Filament\Tables\Columns\TextColumn;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class WarrantyRemindersRelationManager extends RelationManager
{
    protected static string $relationship = 'warrantyReminders';

    protected static ?string $recordTitleAttribute = 'amenity_id';

    // public static function getTitle(): string
    // {
    //     return __('Warranty Reminder Deactivation');
    // }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('unit.unit_number')
                ->label(__('Unit Number')),
                TextColumn::make('user.name')
                ->label(__('app.name')),
                TextColumn::make('user.email')
                ->label(__('app.email')),
                TextColumn::make('created_at')
                ->label(__('Stop Remind At')),
            ])
            ->filters([
                //
            ]);
    }
}
