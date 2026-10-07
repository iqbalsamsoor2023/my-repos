<?php

namespace App\Filament\Resources\VehicleBrands;

use App\Filament\Resources\VehicleBrands\Pages\CreateVehicleBrands;
use App\Filament\Resources\VehicleBrands\Pages\EditVehicleBrands;
use App\Filament\Resources\VehicleBrands\Pages\ListVehicleBrands;
use App\Filament\Resources\VehicleBrands\Schemas\VehicleBrandForm;
use App\Filament\Resources\VehicleBrands\Tables\VehicleBrandsTable;
use App\Models\VehicleBrand;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VehicleBrandResource extends Resource
{
    protected static ?string $model = VehicleBrand::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookmarkSquare;

    protected static ?int $navigationSort = 12;

    public static function getNavigationGroup(): string
    {
        return __('menu.setting_management');
    }

    public static function getModelLabel(): string
    {
        return __('vehicle.vehicle_brand');
    }

    public static function getPluralModelLabel(): string
    {
        return __('vehicle.vehicle_brands');
    }

    public static function getNavigationLabel(): string
    {
        return __('vehicle.vehicle_brands');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasAnyRole(['Super Admin', 'Admin']);
    }

    public static function form(Schema $schema): Schema
    {
        return VehicleBrandForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VehicleBrandsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVehicleBrands::route('/'),
            'create' => CreateVehicleBrands::route('/create'),
            'edit' => EditVehicleBrands::route('/{record}/edit'),
        ];
    }
}
