<?php

namespace App\Filament\Resources\PrivateClaimItemOptions;

use App\Filament\Resources\PrivateClaimItemOptions\Pages\CreatePrivateClaimItemOption;
use App\Filament\Resources\PrivateClaimItemOptions\Pages\EditPrivateClaimItemOption;
use App\Filament\Resources\PrivateClaimItemOptions\Pages\ListPrivateClaimItemOptions;
use App\Filament\Resources\PrivateClaimItemOptions\Schemas\PrivateClaimItemOptionForm;
use App\Filament\Resources\PrivateClaimItemOptions\Tables\PrivateClaimItemOptionsTable;
use App\Models\PrivateClaimItemOption;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PrivateClaimItemOptionResource extends Resource
{
    protected static ?string $model = PrivateClaimItemOption::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): string
    {
        return __('menu.setting_management');
    }
    
    public static function getModelLabel(): string
    {
        return __('maintenance.private_claim_item_option');
    }

    public static function getPluralModelLabel(): string
    {
        return __('maintenance.private_claim_item_options');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.settingManagement.private_claim_item_options');
    }


    public static function form(Schema $schema): Schema
    {
        return PrivateClaimItemOptionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PrivateClaimItemOptionsTable::configure($table);
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
            'index' => ListPrivateClaimItemOptions::route('/'),
            'create' => CreatePrivateClaimItemOption::route('/create'),
            'edit' => EditPrivateClaimItemOption::route('/{record}/edit'),
        ];
    }
}
