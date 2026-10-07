<?php

namespace App\Filament\Resources\Banks;

use App\Filament\Resources\Banks\Pages\ListBanks;
use App\Filament\Resources\Banks\Schemas\BankForm;
use App\Filament\Resources\Banks\Tables\BanksTable;
use App\Models\Bank;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BankResource extends Resource
{
    protected static ?string $model = Bank::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string
    {
        return __('menu.setting_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.settingManagement.banks');
    }

    public static function getModelLabel(): string
    {
        return __('bank.bank');
    }

    public static function form(Schema $schema): Schema
    {
        return BankForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BanksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBanks::route('/'),
        ];
    }
}
