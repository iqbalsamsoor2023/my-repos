<?php

namespace App\Filament\Resources\MaintenanceProgressions;

use Filament\Schemas\Schema;
use App\Filament\Resources\MaintenanceProgressions\Pages\ListMaintenanceProgressions;
use App\Filament\Resources\MaintenanceProgressions\Pages\CreateMaintenanceProgression;
use App\Filament\Resources\MaintenanceProgressions\Pages\EditMaintenanceProgression;
use App\Filament\Resources\MaintenanceProgressions\Schemas\MaintenanceProgressionForm;
use App\Filament\Resources\MaintenanceProgressions\Tables\MaintenanceProgressionsTable;
use App\Models\MaintenanceProgression;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class MaintenanceProgressionResource extends Resource
{
    protected static ?string $model = MaintenanceProgression::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static bool $shouldRegisterNavigation = false;

    public static function getModelLabel(): string
    {
        return __('maintenance.maintenance_progression');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.maintenance_progressions');
    }

    public static function form(Schema $schema): Schema
    {
        return MaintenanceProgressionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaintenanceProgressionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaintenanceProgressions::route('/'),
            'create' => CreateMaintenanceProgression::route('/create'),
            'edit' => EditMaintenanceProgression::route('/{record}/edit'),
        ];
    }
}
