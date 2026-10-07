<?php

namespace App\Filament\Resources\FacilityAndAmenities;

use App\Filament\Resources\FacilityAndAmenities\Pages\CreateFacilityAndAmenity;
use App\Filament\Resources\FacilityAndAmenities\Pages\EditFacilityAndAmenity;
use App\Filament\Resources\FacilityAndAmenities\Pages\ListFacilityAndAmenities;
use App\Filament\Resources\FacilityAndAmenities\Schemas\FacilityAndAmenityForm;
use App\Filament\Resources\FacilityAndAmenities\Tables\FacilityAndAmenitiesTable;
use App\Models\FacilityAndAmenity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FacilityAndAmenityResource extends Resource
{
    protected static ?string $model = FacilityAndAmenity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'facilities-and-amenities';

    public static function getNavigationGroup(): string
    {
        return __('menu.setting_management');
    }

    public static function getModelLabel(): string
    {
        return __('menu.facilities_amenities');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.facilities_amenities');
    }

    public static function form(Schema $schema): Schema
    {
        return FacilityAndAmenityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FacilityAndAmenitiesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFacilityAndAmenities::route('/'),
            'create' => CreateFacilityAndAmenity::route('/create'),
            'edit' => EditFacilityAndAmenity::route('/{record}/edit'),
        ];
    }
}
