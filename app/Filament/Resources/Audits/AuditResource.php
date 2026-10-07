<?php

namespace App\Filament\Resources\Audits;

use App\Filament\Resources\Audits\Pages\ListAudits;
use App\Filament\Resources\Audits\Schemas\AuditForm;
use App\Filament\Resources\Audits\Tables\AuditsTable;
use App\Models\Audit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AuditResource extends Resource
{
    protected static ?string $model = Audit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    public static function getNavigationLabel(): string
    {
        return __('app.audit');
    }

    public static function getModelLabel(): string
    {
        return __('app.audit');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.audit');
    }

    public static function form(Schema $schema): Schema
    {
        return AuditForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AuditsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAudits::route('/'),
        ];
    }
}
