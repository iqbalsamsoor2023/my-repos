<?php

namespace App\Filament\Resources\BlacklistVisitors;

use App\Filament\Resources\BlacklistVisitors\Pages\CreateBlacklistVisitor;
use App\Filament\Resources\BlacklistVisitors\Pages\EditBlacklistVisitor;
use App\Filament\Resources\BlacklistVisitors\Pages\ListBlacklistVisitors;
use App\Filament\Resources\BlacklistVisitors\Schemas\BlacklistVisitorForm;
use App\Filament\Resources\BlacklistVisitors\Tables\BlacklistVisitorsTable;
use App\Models\BlacklistedVisitor;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class BlacklistVisitorResource extends Resource
{
    protected static ?string $model = BlacklistedVisitor::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-x-circle';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): string
    {
        return __('menu.security_data');
    }

    public static function form(Schema $schema): Schema
    {
        return BlacklistVisitorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlacklistVisitorsTable::configure($table);
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
            'index' => ListBlacklistVisitors::route('/'),
            'create' => CreateBlacklistVisitor::route('/create'),
            'edit' => EditBlacklistVisitor::route('/{record}/edit'),
        ];
    }
}
