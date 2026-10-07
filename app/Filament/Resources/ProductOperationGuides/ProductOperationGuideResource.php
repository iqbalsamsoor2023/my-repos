<?php

namespace App\Filament\Resources\ProductOperationGuides;

use App\Filament\Resources\ProductOperationGuides\Pages\CreateProductOperationGuide;
use App\Filament\Resources\ProductOperationGuides\Pages\EditProductOperationGuide;
use App\Filament\Resources\ProductOperationGuides\Pages\ListProductOperationGuides;
use App\Filament\Resources\ProductOperationGuides\Schemas\ProductOperationGuideForm;
use App\Filament\Resources\ProductOperationGuides\Tables\ProductOperationGuidesTable;
use App\Models\Erp\ResourceMaterial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class ProductOperationGuideResource extends Resource
{
    use Translatable;

    protected static ?string $model = ResourceMaterial::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'product-operation-guides';

    protected static ?string $modelLabel = 'POG';

    public static function getNavigationGroup(): string
    {
        return __('menu.pog_and_user_tutorial');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.pog.product_operation_guides');
    }

    public static function form(Schema $schema): Schema
    {
        return ProductOperationGuideForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductOperationGuidesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductOperationGuides::route('/'),
            'create' => CreateProductOperationGuide::route('/create'),
            'edit' => EditProductOperationGuide::route('/{record}/edit'),
        ];
    }
}
