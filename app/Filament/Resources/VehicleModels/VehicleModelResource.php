<?php

namespace App\Filament\Resources\VehicleModels;

use App\Filament\Resources\VehicleModels\Pages\CreateVehicleModel;
use App\Filament\Resources\VehicleModels\Pages\EditVehicleModel;
use App\Filament\Resources\VehicleModels\Pages\ListVehicleModels;
use App\Filament\Resources\VehicleModels\Schemas\VehicleModelForm;
use App\Filament\Resources\VehicleModels\Tables\VehicleModelsTable;
use App\Models\VehicleModel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VehicleModelResource extends Resource
{
    protected static ?string $model = VehicleModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 11;

    public static function getNavigationGroup(): string
    {
        return __('menu.setting_management');
    }

    public static function getModelLabel(): string
    {
        return __('vehicle.vehicle_model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('vehicle.vehicle_models');
    }

    public static function getNavigationLabel(): string
    {
        return __('vehicle.vehicle_models');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasAnyRole(['Super Admin', 'Admin']);
    }

    public static function form(Schema $schema): Schema
    {
        return VehicleModelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VehicleModelsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVehicleModels::route('/'),
            'create' => CreateVehicleModel::route('/create'),
            'edit' => EditVehicleModel::route('/{record}/edit'),
        ];
    }
}
