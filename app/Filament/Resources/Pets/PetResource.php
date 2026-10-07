<?php

namespace App\Filament\Resources\Pets;

use App\Filament\Resources\Pets\Pages\CreatePet;
use App\Filament\Resources\Pets\Pages\EditPet;
use App\Filament\Resources\Pets\Pages\ListPets;
use App\Filament\Resources\Pets\Schemas\PetForm;
use App\Filament\Resources\Pets\Tables\PetsTable;
use App\Models\Pet;
use App\Policies\PetPolicy;
use App\Support\QueryGuardSupport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PetResource extends Resource
{
    protected static ?string $model = Pet::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquaresPlus;

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): string
    {
        return __('menu.asset_management');
    }

    public static function getModelLabel(): string
    {
        return __('pet.pet');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.assetManagement.manage_pets');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.assetManagement.manage_pets');
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->can('viewAny', Pet::class) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return PetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PetsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPets::route('/'),
            'create' => CreatePet::route('/create'),
            'edit' => EditPet::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        $query = parent::getEloquentQuery()
            ->with([
                'unit:id,unit_number,residence_id',
                'unit.residence:id,name,name_th',
                'user:id,name',
            ]);

        if (PetPolicy::isPropertyManager($user)) {
            $query->whereHas('unit.residence', fn ($q) => $q->where('property_management_user_id', $user->id));
        } elseif (PetPolicy::isOperationCenter($user)) {
            $residenceIds = array_values(array_filter((array) get_residence_by_property_management_operation_center($user->id)));

            QueryGuardSupport::whereHasInOrDenyAll($query, 'unit', 'residence_id', $residenceIds);
        }

        return $query;
    }
}
