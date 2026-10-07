<?php

namespace App\Filament\Resources\PrivateClaimCategories;

use App\Filament\Resources\PrivateClaimCategories\Pages\CreatePrivateClaimCategory;
use App\Filament\Resources\PrivateClaimCategories\Pages\EditPrivateClaimCategory;
use App\Filament\Resources\PrivateClaimCategories\Pages\ListPrivateClaimCategories;
use App\Filament\Resources\PrivateClaimCategories\Schemas\PrivateClaimCategoryForm;
use App\Filament\Resources\PrivateClaimCategories\Tables\PrivateClaimCategoriesTable;
use App\Models\PrivateClaimCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PrivateClaimCategoryResource extends Resource
{
    protected static ?string $model = PrivateClaimCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): string
    {
        return __('menu.setting_management');
    }
    
    public static function getModelLabel(): string
    {
        return __('maintenance.private_claim_category');
    }

    public static function getPluralModelLabel(): string
    {
        return __('maintenance.private_claim_categories');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.settingManagement.private_claim_categories');
    }

    public static function form(Schema $schema): Schema
    {
        return PrivateClaimCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PrivateClaimCategoriesTable::configure($table);
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
            'index' => ListPrivateClaimCategories::route('/'),
            'create' => CreatePrivateClaimCategory::route('/create'),
            'edit' => EditPrivateClaimCategory::route('/{record}/edit'),
        ];
    }
}
