<?php

namespace App\Filament\Resources\Users;

use App\Enums\User\RoleType;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 1;

    protected $record;

    public static function getNavigationGroup(): string
    {
        return __('menu.user_management');
    }

    public static function getModelLabel(): string
    {
        return __('user.user');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.userManagement.manage_users');
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasAnyRole([RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value])) {
            return true;
        }

        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::ADMIN->value,
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
