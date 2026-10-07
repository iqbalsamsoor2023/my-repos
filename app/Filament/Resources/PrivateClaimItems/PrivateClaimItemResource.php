<?php

namespace App\Filament\Resources\PrivateClaimItems;

use App\Filament\Resources\PrivateClaimItems\Pages\CreatePrivateClaimItem;
use App\Filament\Resources\PrivateClaimItems\Pages\EditPrivateClaimItem;
use App\Filament\Resources\PrivateClaimItems\Pages\ListPrivateClaimItems;
use App\Filament\Resources\PrivateClaimItems\Schemas\PrivateClaimItemForm;
use App\Filament\Resources\PrivateClaimItems\Tables\PrivateClaimItemsTable;
use App\Models\PrivateClaimItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PrivateClaimItemResource extends Resource
{
    protected static ?string $model = PrivateClaimItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): string
    {
        return __('menu.setting_management');
    }
    
    public static function getModelLabel(): string
    {
        return __('maintenance.private_claim_item');
    }

    public static function getPluralModelLabel(): string
    {
        return __('maintenance.private_claim_items');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.settingManagement.private_claim_items');
    }

    public static function form(Schema $schema): Schema
    {
        return PrivateClaimItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PrivateClaimItemsTable::configure($table);
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
            'index' => ListPrivateClaimItems::route('/'),
            'create' => CreatePrivateClaimItem::route('/create'),
            'edit' => EditPrivateClaimItem::route('/{record}/edit'),
        ];
    }
}
