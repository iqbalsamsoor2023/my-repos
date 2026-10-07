<?php

namespace App\Filament\Resources\Vehicles;

use App\Filament\Resources\Vehicles\Pages\CreateVehicle;
use App\Filament\Resources\Vehicles\Pages\EditVehicle;
use App\Filament\Resources\Vehicles\Pages\ListVehicles;
use App\Filament\Resources\Vehicles\Schemas\VehicleForm;
use App\Filament\Resources\Vehicles\Tables\VehiclesTable;
use App\Models\Vehicle;
use App\Support\VehicleDashboardSupport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string
    {
        return __('menu.asset_management');
    }

    public static function getModelLabel(): string
    {
        return __('vehicle.vehicle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.assetManagement.manage_vehicles');
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->can('viewAny', Vehicle::class) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return VehicleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VehiclesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVehicles::route('/'),
            'create' => CreateVehicle::route('/create'),
            'edit' => EditVehicle::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        $prefilter = VehicleDashboardSupport::vehicleIdScopeSubquery($user);

        return parent::getEloquentQuery()
            ->whereIn('id', $prefilter)
            ->with([
                'unit:id,unit_number,residence_id',
                'unit.residence:id,name,name_th',
                'user:id,name,email,phone_no',
                'province:id,name_in_english,name_in_thai',
                'vehicleModel:id,type,vehicle_brand_id',
                'vehicleModel.vehicleBrand:id,name',
                'insuranceCompany:id,name,name_th',
            ]);
    }
}
