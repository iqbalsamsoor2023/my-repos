<?php

namespace App\Filament\Resources\ResidenceAmenities;

use App\Filament\Resources\ResidenceAmenities\Pages\CreateResidenceAmenity;
use App\Filament\Resources\ResidenceAmenities\Pages\EditResidenceAmenity;
use App\Filament\Resources\ResidenceAmenities\Pages\ListResidenceAmenities;
use App\Filament\Resources\ResidenceAmenities\Pages\ViewResidenceAmenity;
use App\Filament\Resources\ResidenceAmenities\Schemas\ResidenceAmenityForm;
use App\Filament\Resources\ResidenceAmenities\Tables\ResidenceAmenitiesTable;
use App\Models\ResidenceAmenity;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ResidenceAmenityResource extends Resource
{
    protected static ?string $model = ResidenceAmenity::class;

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): string
    {
        return __('menu.operation_management');
    }

    public static function getModelLabel(): string
    {
        return __('menu.residence_amenities_setting');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.residence_amenities_setting');
    }

    public static function form(Schema $schema): Schema
    {
        return ResidenceAmenityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ResidenceAmenitiesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListResidenceAmenities::route('/'),
            'create' => CreateResidenceAmenity::route('/create'),
            'view' => ViewResidenceAmenity::route('/{record}'),
            'edit' => EditResidenceAmenity::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery()->where('is_active', true)
            ->where(function ($q) {
                $q->where('is_bookable', true)
                    ->orWhere('is_claimable', true);
            })
            ->whereHas('facilityAndAmenity', function ($q) {
                $q->where('name', '!=', 'Others');
            });

        if ($user->hasRole('Property Management')) {
            $query->whereHas('residence', fn ($r) => $r->where('property_management_user_id', $user->id));
        } elseif ($user->hasRole('Property Management Operation Center')) {
            $residence_ids = get_residence_by_property_management_operation_center($user->id);

            $query->whereIn('residence_id', $residence_ids);
        }

        return $query;
    }
}
