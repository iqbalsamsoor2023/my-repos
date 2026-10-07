<?php

namespace App\Filament\Resources\PrivateClaimSuppliers;

use App\Filament\Resources\PrivateClaimSuppliers\Pages\CreatePrivateClaimSupplier;
use App\Filament\Resources\PrivateClaimSuppliers\Pages\EditPrivateClaimSupplier;
use App\Filament\Resources\PrivateClaimSuppliers\Pages\ListPrivateClaimSuppliers;
use App\Filament\Resources\PrivateClaimSuppliers\Schemas\PrivateClaimSupplierForm;
use App\Filament\Resources\PrivateClaimSuppliers\Tables\PrivateClaimSuppliersTable;
use App\Models\PrivateClaimSupplier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PrivateClaimSupplierResource extends Resource
{
    protected static ?string $model = PrivateClaimSupplier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    // public static function getNavigationGroup(): string
    // {
    //     return __('menu.setting_management');
    // }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return PrivateClaimSupplierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PrivateClaimSuppliersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrivateClaimSuppliers::route('/'),
            'create' => CreatePrivateClaimSupplier::route('/create'),
            'edit' => EditPrivateClaimSupplier::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        if ($user->hasRole('Property Management')) {
            $query->whereHas('residence', function (Builder $q) use ($user) {
                $q->where('property_management_user_id', $user->id);
            });
        }

        return $query;
    }
}
