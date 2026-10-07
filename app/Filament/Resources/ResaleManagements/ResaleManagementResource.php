<?php

namespace App\Filament\Resources\ResaleManagements;

use App\Filament\Resources\ResaleManagements\Pages\CreateResaleManagement;
use App\Filament\Resources\ResaleManagements\Pages\EditResaleManagement;
use App\Filament\Resources\ResaleManagements\Pages\ListResaleManagements;
use App\Filament\Resources\ResaleManagements\Schemas\ResaleManagementForm;
use App\Filament\Resources\ResaleManagements\Tables\ResaleManagementsTable;
use App\Models\ResaleAdvertisement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ResaleManagementResource extends Resource
{
    protected static ?string $model = ResaleAdvertisement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('menu.resale_management');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('menu.re_sales_tenancy');
    }

    public static function getModelLabel(): string
    {
        return __('resales-and-tenancies.resale_management');
    }

    public static function form(Schema $schema): Schema
    {
        return ResaleManagementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ResaleManagementsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListResaleManagements::route('/'),
            'create' => CreateResaleManagement::route('/create'),
            'edit' => EditResaleManagement::route('/{record}/edit'),
        ];
    }
}
