<?php

namespace App\Filament\Resources\SaleManagements;

use App\Filament\Resources\SaleManagements\Pages\CreateSaleManagement;
use App\Filament\Resources\SaleManagements\Pages\EditSaleManagement;
use App\Filament\Resources\SaleManagements\Pages\ListSaleManagement;
use App\Filament\Resources\SaleManagements\Schemas\SaleManagementForm;
use App\Filament\Resources\SaleManagements\Tables\SaleManagementsTable;
use App\Models\SaleAdvertisement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SaleManagementResource extends Resource
{
    protected static ?string $model = SaleAdvertisement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCubeTransparent;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('sale.sale_management');
    }

    public static function getModelLabel(): string
    {
        return __('sale.sale_management');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.sales.sales_managements');
    }

    public static function form(Schema $schema): Schema
    {
        return SaleManagementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SaleManagementsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSaleManagement::route('/'),
            'create' => CreateSaleManagement::route('/create'),
            'edit' => EditSaleManagement::route('/{record}/edit'),
        ];
    }
}
