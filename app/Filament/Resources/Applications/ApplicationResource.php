<?php

namespace App\Filament\Resources\Applications;

use App\Filament\Resources\Applications\Pages\CreateApp;
use App\Filament\Resources\Applications\Pages\EditApp;
use App\Filament\Resources\Applications\Pages\ListApps;
use App\Filament\Resources\Applications\RelationManagers\AppVersionsRelationManager;
use App\Filament\Resources\Applications\Schemas\ApplicationForm;
use App\Filament\Resources\Applications\Tables\ApplicationsTable;
use App\Models\Application;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string
    {
        return __('menu.administrator_management');
    }

    public static function getModelLabel(): string
    {
        return __('app.app_version');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.app_versions');
    }

    public static function form(Schema $schema): Schema
    {
        return ApplicationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApplicationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApps::route('/'),
            'create' => CreateApp::route('/create'),
            'edit' => EditApp::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            AppVersionsRelationManager::class,
        ];
    }
}
