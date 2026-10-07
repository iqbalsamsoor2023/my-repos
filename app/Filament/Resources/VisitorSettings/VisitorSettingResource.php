<?php

namespace App\Filament\Resources\VisitorSettings;

use App\Filament\Resources\VisitorSettings\Pages\CreateVisitorSetting;
use App\Filament\Resources\VisitorSettings\Pages\EditVisitorSetting;
use App\Filament\Resources\VisitorSettings\Pages\ListVisitorSettings;
use App\Filament\Resources\VisitorSettings\Schemas\VisitorSettingForm;
use App\Filament\Resources\VisitorSettings\Tables\VisitorSettingsTable;
use App\Models\VisitorSetting;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class VisitorSettingResource extends Resource
{
    protected static ?string $model = VisitorSetting::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): string
    {
        return __('menu.security_data');
    }

    public static function form(Schema $schema): Schema
    {
        return VisitorSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VisitorSettingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVisitorSettings::route('/'),
            'create' => CreateVisitorSetting::route('/create'),
            'edit' => EditVisitorSetting::route('/{record}/edit'),
        ];
    }
}
