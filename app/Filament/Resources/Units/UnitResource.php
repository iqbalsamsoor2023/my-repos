<?php

namespace App\Filament\Resources\Units;

use App\Filament\Resources\Units\Pages\CreateUnit;
use App\Filament\Resources\Units\Pages\EditUnit;
use App\Filament\Resources\Units\Pages\ListUnits;
use App\Filament\Resources\Units\RelationManagers\FurnituresRelationManager;
use App\Filament\Resources\Units\RelationManagers\HomeAppliancesRelationManager;
use App\Filament\Resources\Units\RelationManagers\LivingSpaceRelationManager;
use App\Filament\Resources\Units\RelationManagers\MaintenancesRelationManager;
use App\Filament\Resources\Units\RelationManagers\OwnersRelationManager;
use App\Filament\Resources\Units\RelationManagers\ParcelsRelationManager;
use App\Filament\Resources\Units\RelationManagers\PetsRelationManager;
use App\Filament\Resources\Units\RelationManagers\TenantsRelationManager;
use App\Filament\Resources\Units\RelationManagers\VehiclesRelationManager;
use App\Filament\Resources\Units\Schemas\UnitForm;
use App\Filament\Resources\Units\Tables\UnitsTable;
use App\Models\Unit;
use App\Policies\UnitPolicy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string
    {
        return __('menu.asset_management');
    }

    public static function getModelLabel(): string
    {
        return __('menu.assetManagement.manage_units');
    }

    public static function form(Schema $schema): Schema
    {
        return UnitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UnitsTable::configure($table);
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50];
    }

    public static function getRelations(): array
    {
        return [
            OwnersRelationManager::class,
            TenantsRelationManager::class,
            MaintenancesRelationManager::class,
            ParcelsRelationManager::class,
            PetsRelationManager::class,
            VehiclesRelationManager::class,
            FurnituresRelationManager::class,
            HomeAppliancesRelationManager::class,
            LivingSpaceRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUnits::route('/'),
            'create' => CreateUnit::route('/create'),
            'edit' => EditUnit::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = Unit::query()
            ->whereNull('units.deleted_at', 'and', false)
            ->whereHas('residence', fn (Builder $query) => $query->whereNull('deleted_at'))
            ->select([
                'units.id',
                'units.residence_id',
                'units.unit_number',
                'units.home_id',
                'units.block',
                'units.street',
                'units.floor',
                'units.unit_size',
                'units.land_size',
                'units.sub_type',
                'units.house_type',
                'units.status',
                'units.move_in_at',
                'units.charge_type',
                'units.maintenance_cycle',
                'units.construction_progress',
                'units.invitation_code_owner',
                'units.invitation_code_tenant',
                'units.myseevr_link',
                'units.sample_room_vr_link',
                'units.created_at',
                'units.updated_at',
                'units.deleted_at',
            ])
            ->with([
                'residence:id,name,name_th,mooban_type,sub_type,subdistrict_id,property_management_user_id,property_management_id,residence_activation_status_id',
                'residence.subdistrict:id,district_id,name_in_english,name_in_thai',
                'residence.subdistrict.district:id,province_id,name_in_english,name_in_thai',
                'residence.subdistrict.district.province:id,name_in_english,name_in_thai',
                'owners:id,unit_id,user_id,is_owner',
                'owners.user:id,name,email',
                'tenants:id,unit_id,user_id,is_owner',
                'tenants.user:id,name,email',
                'rentAdvertisement:id,unit_id,tenancy_status',
                'resaleAdvertisement:id,unit_id,status',
            ]);

        if (UnitPolicy::isPropertyManager($user)) {
            $query->whereHas('residence', fn (Builder $residenceQuery): Builder => $residenceQuery->where('property_management_user_id', $user->id));
        } elseif (UnitPolicy::isOperationCenter($user)) {
            $residenceIds = get_residence_by_property_management_operation_center($user->id);
            $query->whereIn('units.residence_id', $residenceIds);
        } elseif (UnitPolicy::isResalesAndTenancy($user)) {
            $residenceIds = get_residence_id_list_by_rtm($user->id);
            $query->whereIn('units.residence_id', $residenceIds);
        } elseif (UnitPolicy::isSalesManagement($user)) {
            $residenceIdList = get_residence_id_list_by_sm($user->id);
            $query->whereIn('units.residence_id', $residenceIdList);
        }

        return $query;
    }
}
