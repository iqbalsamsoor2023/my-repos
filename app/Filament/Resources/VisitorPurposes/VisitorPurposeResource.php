<?php

namespace App\Filament\Resources\VisitorPurposes;

use App\Filament\Resources\VisitorPurposes\Pages\CreateVisitorPurpose;
use App\Filament\Resources\VisitorPurposes\Pages\EditVisitorPurpose;
use App\Filament\Resources\VisitorPurposes\Pages\ListVisitorPurposes;
use App\Filament\Resources\VisitorPurposes\Schemas\VisitorPurposeForm;
use App\Filament\Resources\VisitorPurposes\Tables\VisitorPurposesTable;
use App\Models\VisitorPurpose;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class VisitorPurposeResource extends Resource
{
    protected static ?string $model = VisitorPurpose::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): string
    {
        return __('menu.security_data');
    }

    public static function getModelLabel(): string
    {
        return __('visitor.visitor_purpose');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.visitor_purposes');
    }

    public static function form(Schema $schema): Schema
    {
        return VisitorPurposeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VisitorPurposesTable::configure($table);
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
            'index' => ListVisitorPurposes::route('/'),
            'create' => CreateVisitorPurpose::route('/create'),
            'edit' => EditVisitorPurpose::route('/{record}/edit'),
        ];
    }
}
