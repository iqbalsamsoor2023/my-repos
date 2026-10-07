<?php

namespace App\Filament\Resources\ResidenceAmenitySummaries;

use App\Enums\User\RoleType;
use App\Filament\Resources\ResidenceAmenitySummaries\Pages\ListResidenceAmenitySummaries;
use App\Filament\Resources\ResidenceAmenitySummaries\Tables\ResidenceAmenitySummariesTable;
use App\Models\Residence;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ResidenceAmenitySummaryResource extends Resource
{
    protected static ?string $model = Residence::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3BottomLeft;

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): string
    {
        return __('menu.big_data_management');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasAnyRole([RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole([RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value]);
    }

    public static function getModelLabel(): string
    {
        return __('menu.residence_amenity_facility');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.residence_amenity_facility');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return ResidenceAmenitySummariesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListResidenceAmenitySummaries::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return Residence::with([
            'residenceAmenities' => fn ($query) => $query->whereNull('deleted_at'),
            'subdistrict.district.province',
            'propertyManagementUser',
        ]);

    }
}
