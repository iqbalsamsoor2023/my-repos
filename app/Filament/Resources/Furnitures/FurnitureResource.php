<?php

namespace App\Filament\Resources\Furnitures;

use App\Enums\User\RoleType;
use App\Filament\Resources\Furnitures\Pages\CreateFurniture;
use App\Filament\Resources\Furnitures\Pages\EditFurniture;
use App\Filament\Resources\Furnitures\Pages\ListFurniture;
use App\Filament\Resources\Furnitures\Schemas\FurnitureForm;
use App\Filament\Resources\Furnitures\Tables\FurnituresTable;
use App\Models\HouseholdItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FurnitureResource extends Resource
{
    protected static ?string $model = HouseholdItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string
    {
        return __('menu.setting_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.furnitures');
    }

    public static function getModelLabel(): string
    {
        return __('resales-and-tenancy.furniture');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasAnyRole([RoleType::SUPER_ADMIN->value]);
    }

    public static function form(Schema $schema): Schema
    {
        return FurnitureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FurnituresTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFurniture::route('/'),
            'create' => CreateFurniture::route('/create'),
            'edit' => EditFurniture::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->Furniture();
    }
}
