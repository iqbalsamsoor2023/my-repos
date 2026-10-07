<?php

namespace App\Filament\Resources\UserHealths;

use App\Filament\Resources\UserHealths\Pages\ListUserHealths;
use App\Filament\Resources\UserHealths\Tables\UserHealthsTable;
use App\Models\UserHealth;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserHealthResource extends Resource
{
    protected static ?string $model = UserHealth::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string
    {
        return __('menu.user_management');
    }

    public static function getModelLabel(): string
    {
        return __('menu.userManagement.manage_user_healths');
    }

    public static function table(Table $table): Table
    {
        return UserHealthsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserHealths::route('/'),
            // 'create' => Pages\CreateUserHealth::route('/create'),
            // 'edit' => Pages\EditUserHealth::route('/{record}/edit'),
        ];
    }
}
