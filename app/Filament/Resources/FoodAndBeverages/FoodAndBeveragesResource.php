<?php

namespace App\Filament\Resources\FoodAndBeverages;

use App\Enums\LogisticPartner\CategoryType;
use App\Filament\Resources\FoodAndBeverages\Pages\CreateFoodAndBeverages;
use App\Filament\Resources\FoodAndBeverages\Pages\EditFoodAndBeverages;
use App\Filament\Resources\FoodAndBeverages\Pages\ListFoodAndBeverages;
use App\Filament\Resources\LogisticPartners\LogisticPartnerResource;
use App\Models\LogisticPartner;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FoodAndBeveragesResource extends Resource
{
    protected static ?string $model = LogisticPartner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsVertical;

    protected static ?int $navigationSort = 9;

    protected static ?string $slug = 'foods-and-beverages';

    public static function getNavigationGroup(): string
    {
        return __('menu.setting_management');
    }

    public static function getModelLabel(): string
    {
        return __('Food Delivery');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.food_deliveries');
    }

    public static function form(Schema $schema): Schema
    {
        return LogisticPartnerResource::form($schema);
    }

    public static function table(Table $table): Table
    {
        return LogisticPartnerResource::table($table)
            ->filtersLayout(FiltersLayout::AboveContentCollapsible);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFoodAndBeverages::route('/'),
            'create' => CreateFoodAndBeverages::route('/create'),
            'edit' => EditFoodAndBeverages::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return LogisticPartner::query()->where('category', '=', CategoryType::FoodDelivery->value);
    }
}
