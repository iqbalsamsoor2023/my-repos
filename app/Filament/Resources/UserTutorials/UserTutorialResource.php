<?php

namespace App\Filament\Resources\UserTutorials;

use App\Filament\Resources\UserTutorials\Pages\CreateUserTutorial;
use App\Filament\Resources\UserTutorials\Pages\EditUserTutorial;
use App\Filament\Resources\UserTutorials\Pages\ListUserTutorials;
use App\Filament\Resources\UserTutorials\Pages\ViewUserTutorial;
use App\Filament\Resources\UserTutorials\Schemas\UserTutorialForm;
use App\Filament\Resources\UserTutorials\Tables\UserTutorialsTable;
use App\Models\Erp\ResourceMaterial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class UserTutorialResource extends Resource
{
    use Translatable;

    protected static ?string $model = ResourceMaterial::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'user-tutorials';

    public static function getNavigationGroup(): string
    {
        return __('menu.pog_and_user_tutorial');
    }

    public static function getModelLabel(): string
    {
        return __('user.user_tutorial');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.pog.user_tutorials');
    }

    public static function form(Schema $schema): Schema
    {
        return UserTutorialForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserTutorialsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserTutorials::route('/'),
            'create' => CreateUserTutorial::route('/create'),
            'edit' => EditUserTutorial::route('/{record}/edit'),
            'view' => ViewUserTutorial::route('/{record}'),
        ];
    }
}
