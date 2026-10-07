<?php

namespace App\Filament\Resources\FaqTypes;

use App\Filament\Resources\FaqTypes\Pages\CreateFaqType;
use App\Filament\Resources\FaqTypes\Pages\EditFaqType;
use App\Filament\Resources\FaqTypes\Pages\ListFaqTypes;
use App\Filament\Resources\FaqTypes\Schemas\FaqTypeForm;
use App\Filament\Resources\FaqTypes\Tables\FaqTypesTable;
use App\Models\Erp\FaqType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class FaqTypeResource extends Resource
{
    use Translatable;

    protected static ?string $model = FaqType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): string
    {
        return __('menu.pog_and_user_tutorial');
    }

    public static function form(Schema $schema): Schema
    {
        return FaqTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FaqTypesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFaqTypes::route('/'),
            'create' => CreateFaqType::route('/create'),
            'edit' => EditFaqType::route('/{record}/edit'),
        ];
    }
}
