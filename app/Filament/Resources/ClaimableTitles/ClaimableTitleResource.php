<?php

namespace App\Filament\Resources\ClaimableTitles;

use App\Filament\Resources\ClaimableTitles\Pages\ListClaimableTitles;
use App\Filament\Resources\ClaimableTitles\Schemas\ClaimableTitleForm;
use App\Filament\Resources\ClaimableTitles\Tables\ClaimableTitlesTable;
use App\Models\ClaimableTitle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ClaimableTitleResource extends Resource
{
    protected static ?string $model = ClaimableTitle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationParentItem = 'Facilities & Amenities';

    public static function getNavigationGroup(): string
    {
        return __('menu.setting_management');
    }

    public static function form(Schema $schema): Schema
    {
        return ClaimableTitleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClaimableTitlesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClaimableTitles::route('/'),
        ];
    }
}
