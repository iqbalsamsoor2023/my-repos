<?php

namespace App\Filament\Resources\BpoSoftwareSuppliers;

use App\Filament\Resources\BpoSoftwareSuppliers\Pages\CreateBpoSoftwareSupplier;
use App\Filament\Resources\BpoSoftwareSuppliers\Pages\EditBpoSoftwareSupplier;
use App\Filament\Resources\BpoSoftwareSuppliers\Pages\ListBpoSoftwareSuppliers;
use App\Filament\Resources\BpoSoftwareSuppliers\Schemas\BpoSoftwareSupplierForm;
use App\Filament\Resources\BpoSoftwareSuppliers\Tables\BpoSoftwareSuppliersTable;
use App\Models\BpoSoftwareSupplier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BpoSoftwareSupplierResource extends Resource
{
    protected static ?string $model = BpoSoftwareSupplier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string
    {
        return __('menu.setting_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.settingManagement.bpo_software_suppliers');
    }

    public static function getModelLabel(): string
    {
        return __('bpo_software.bpo_software_supplier');
    }

    public static function form(Schema $schema): Schema
    {
        return BpoSoftwareSupplierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BpoSoftwareSuppliersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBpoSoftwareSuppliers::route('/'),
            'create' => CreateBpoSoftwareSupplier::route('/create'),
            'edit' => EditBpoSoftwareSupplier::route('/{record}/edit'),
        ];
    }
}
