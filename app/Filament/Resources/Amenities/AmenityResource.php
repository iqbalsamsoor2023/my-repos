<?php

namespace App\Filament\Resources\Amenities;

use App\Filament\Resources\Amenities\Pages\CreateAmenity;
use App\Filament\Resources\Amenities\Pages\EditAmenity;
use App\Filament\Resources\Amenities\Pages\ListAmenities;
use App\Filament\Resources\Amenities\RelationManagers\WarrantyRemindersRelationManager;
use App\Filament\Resources\Amenities\Schemas\AmenityForm;
use App\Filament\Resources\Amenities\Tables\AmenitiesTable;
use App\Models\Amenity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AmenityResource extends Resource
{
    protected static ?string $model = Amenity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): string
    {
        return __('menu.setting_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.amenities');
    }

    public static function getModelLabel(): string
    {
        return __('app.amenity');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasAnyRole(['Super Admin', 'Admin']);
    }

    public static function form(Schema $schema): Schema
    {
        return AmenityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AmenitiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            WarrantyRemindersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAmenities::route('/'),
            'create' => CreateAmenity::route('/create'),
            'edit' => EditAmenity::route('/{record}/edit'),
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

        return $query->with('residence:id,name,property_management_user_id')
            ->select([
                'id',
                'residence_id',
                'amenity_name',
                'warranty_period',
                'period_type',
                'supplier',
                'is_out_warranty',
                'remark',
            ]);
    }
}
