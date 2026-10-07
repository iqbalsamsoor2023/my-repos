<?php

namespace App\Filament\Resources\TenancyManagements;

use App\Filament\Resources\TenancyManagements\Pages\CreateTenancyManagement;
use App\Filament\Resources\TenancyManagements\Pages\EditTenancyManagement;
use App\Filament\Resources\TenancyManagements\Pages\ListTenancyManagements;
use App\Filament\Resources\TenancyManagements\Schemas\TenancyManagementForm;
use App\Filament\Resources\TenancyManagements\Tables\TenancyManagementsTable;
use App\Models\RentAdvertisement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TenancyManagementResource extends Resource
{
    protected static ?string $model = RentAdvertisement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('menu.re_sales_tenancy');
    }

    public static function getModelLabel(): string
    {
        return __('resales-and-tenancies.tenancy_management');
    }

    public static function form(Schema $schema): Schema
    {
        return TenancyManagementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenancyManagementsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTenancyManagements::route('/'),
            'create' => CreateTenancyManagement::route('/create'),
            'edit' => EditTenancyManagement::route('/{record}/edit'),
        ];
    }

    public static function recalculateTotal(callable $set, callable $get): void
    {
        $rentalPrice = floatval($get('rent_price') ?? 0);
        $deposit = floatval($get('deposit') ?? 0);

        if ($get('is_rental_upfront') || $get('../../is_rental_upfront')) {

            $set('total', $rentalPrice + $deposit);
        } else {
            // If not upfront, just set the deposit
            $set('total', $deposit);
        }
    }
}
