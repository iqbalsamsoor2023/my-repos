<?php

namespace App\Filament\Resources\VisitorRemarks;

use App\Filament\Resources\VisitorRemarks\Pages\CreateVisitorRemark;
use App\Filament\Resources\VisitorRemarks\Pages\EditVisitorRemark;
use App\Filament\Resources\VisitorRemarks\Pages\ListVisitorRemarks;
use App\Filament\Resources\VisitorRemarks\Schemas\VisitorRemarkForm;
use App\Filament\Resources\VisitorRemarks\Tables\VisitorRemarksTable;
use App\Models\VisitorRemark;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class VisitorRemarkResource extends Resource
{
    protected static ?string $model = VisitorRemark::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): string
    {
        return __('menu.security_data');
    }

    public static function getModelLabel(): string
    {
        return __('menu.visitor_remarks');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.visitor_remarks');
    }

    public static function form(Schema $schema): Schema
    {
        return VisitorRemarkForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VisitorRemarksTable::configure($table);
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
            'index' => ListVisitorRemarks::route('/'),
            'create' => CreateVisitorRemark::route('/create'),
            'edit' => EditVisitorRemark::route('/{record}/edit'),
        ];
    }
}
