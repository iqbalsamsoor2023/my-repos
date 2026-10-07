<?php

namespace App\Filament\Resources\PrivateClaimItemTitles;

use App\Filament\Resources\PrivateClaimItemTitles\Pages\CreatePrivateClaimItemTitle;
use App\Filament\Resources\PrivateClaimItemTitles\Pages\EditPrivateClaimItemTitle;
use App\Filament\Resources\PrivateClaimItemTitles\Pages\ListPrivateClaimItemTitles;
use App\Filament\Resources\PrivateClaimItemTitles\Schemas\PrivateClaimItemTitleForm;
use App\Filament\Resources\PrivateClaimItemTitles\Tables\PrivateClaimItemTitlesTable;
use App\Models\PrivateClaimItemTitle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PrivateClaimItemTitleResource extends Resource
{
    protected static ?string $model = PrivateClaimItemTitle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): string
    {
        return __('menu.setting_management');
    }
    
    public static function getModelLabel(): string
    {
        return __('maintenance.private_claim_item_title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('maintenance.private_claim_item_titles');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.settingManagement.private_claim_item_titles');
    }


    public static function form(Schema $schema): Schema
    {
        return PrivateClaimItemTitleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PrivateClaimItemTitlesTable::configure($table);
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
            'index' => ListPrivateClaimItemTitles::route('/'),
            'create' => CreatePrivateClaimItemTitle::route('/create'),
            'edit' => EditPrivateClaimItemTitle::route('/{record}/edit'),
        ];
    }
}
