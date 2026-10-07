<?php

namespace App\Filament\Resources\TermsOfServices;

use App\Filament\Resources\TermsOfServices\Pages\CreateTermsOfService;
use App\Filament\Resources\TermsOfServices\Pages\EditTermsOfService;
use App\Filament\Resources\TermsOfServices\Pages\ListTermsOfServices;
use App\Filament\Resources\TermsOfServices\Schemas\TermsOfServiceForm;
use App\Filament\Resources\TermsOfServices\Tables\TermsOfServicesTable;
use App\Models\Erp\StaticContent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class TermsOfServiceResource extends Resource
{
    use Translatable;

    protected static ?string $model = StaticContent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'terms-of-services';

    public static function getNavigationGroup(): string
    {
        return __('menu.pog_and_user_tutorial');
    }

    public static function getModelLabel(): string
    {
        return __('menu.terms_of_service');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.terms_of_service');
    }

    public static function form(Schema $schema): Schema
    {
        return TermsOfServiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TermsOfServicesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTermsOfServices::route('/'),
            'create' => CreateTermsOfService::route('/create'),
            'edit' => EditTermsOfService::route('/{record}/edit'),
        ];
    }
}
