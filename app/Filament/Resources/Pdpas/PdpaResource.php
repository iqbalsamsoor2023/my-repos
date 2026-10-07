<?php

namespace App\Filament\Resources\Pdpas;

use App\Filament\Resources\Pdpas\Pages\CreatePdpa;
use App\Filament\Resources\Pdpas\Pages\EditPdpa;
use App\Filament\Resources\Pdpas\Pages\ListPdpas;
use App\Filament\Resources\Pdpas\Schemas\PdpaForm;
use App\Filament\Resources\Pdpas\Tables\PdpasTable;
use App\Models\Erp\StaticContent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class PdpaResource extends Resource
{
    use Translatable;

    protected static ?string $model = StaticContent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'pdpas';

    protected static ?string $modelLabel = 'PDPA';

    protected static ?string $pluralModelLabel = 'PDPA';

    public static function getNavigationGroup(): string
    {
        return __('menu.pog_and_user_tutorial');
    }

    public static function form(Schema $schema): Schema
    {
        return PdpaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PdpasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPdpas::route('/'),
            'create' => CreatePdpa::route('/create'),
            'edit' => EditPdpa::route('/{record}/edit'),
        ];
    }
}
