<?php

namespace App\Filament\Resources\UnitUsers;

use App\Filament\Resources\UnitUsers\Pages\CreateUnitUser;
use App\Filament\Resources\UnitUsers\Pages\EditUnitUser;
use App\Filament\Resources\UnitUsers\Pages\ListUnitUsers;
use App\Filament\Resources\UnitUsers\Schemas\UnitUserForm;
use App\Filament\Resources\UnitUsers\Tables\UnitUsersTable;
use App\Models\UnitUser;
use App\Policies\UnitUserPolicy;
use App\Support\QueryGuardSupport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class UnitUserResource extends Resource
{
    protected static ?string $model = UnitUser::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string
    {
        return __('menu.asset_management');
    }

    public static function getModelLabel(): string
    {
        return __('app.resident');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.residents');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.assetManagement.manage_residents');
    }

    public static function form(Schema $schema): Schema
    {
        return UnitUserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UnitUsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUnitUsers::route('/'),
            'create' => CreateUnitUser::route('/create'),
            'edit' => EditUnitUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->select([
                'unit_user.id',
                'unit_user.unit_id',
                'unit_user.user_id',
                'unit_user.mmb_id',
                'unit_user.is_owner',
                'unit_user.is_main_owner',
                'unit_user.is_main_tenant',
                'unit_user.relationship',
                'unit_user.approval_status',
                'unit_user.created_at',
                'unit_user.updated_at',
                'unit_user.deleted_at',
            ])
            ->with([
                'unit:id,residence_id,unit_number',
                'unit.residence:id,name,name_th',
                'user:id,name,email,phone_no,email_verified_at,gender,country_id,date_of_birth',
            ]);

        $user = Auth::user();

        if (UnitUserPolicy::isPropertyManager($user)) {
            $residence = get_residence_by_property_management($user->id);

            if (! $residence) {
                return QueryGuardSupport::denyAll($query);
            }

            $query->whereHas('unit', function ($subQuery) use ($residence) {
                $subQuery->where('residence_id', $residence->id);
            });
        } elseif (UnitUserPolicy::isOperationCenter($user)) {
            $residenceIds = array_values(array_filter((array) get_residence_by_property_management_operation_center($user->id)));

            QueryGuardSupport::whereHasInOrDenyAll($query, 'unit', 'residence_id', $residenceIds);
        }

        return $query;
    }
}
