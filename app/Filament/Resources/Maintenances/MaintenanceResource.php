<?php

namespace App\Filament\Resources\Maintenances;

use App\Filament\Resources\Maintenances\Pages\EditMaintenance;
use App\Filament\Resources\Maintenances\Pages\ListMaintenances;
use App\Filament\Resources\Maintenances\Pages\ViewMaintenance;
use App\Filament\Resources\Maintenances\RelationManagers\MaintenanceProgressionsRelationManager;
use App\Filament\Resources\Maintenances\Schemas\MaintenanceForm;
use App\Filament\Resources\Maintenances\Tables\MaintenancesTable;
use App\Models\Maintenance;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class MaintenanceResource extends Resource
{
    protected static ?string $model = Maintenance::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): string
    {
        return __('menu.maintenance_data');
    }

    public static function form(Schema $schema): Schema
    {
        return MaintenanceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaintenancesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            MaintenanceProgressionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaintenances::route('/'),
            'edit' => EditMaintenance::route('/{record}/edit'),
            'view' => ViewMaintenance::route('/{record}'),
        ];
    }
}
