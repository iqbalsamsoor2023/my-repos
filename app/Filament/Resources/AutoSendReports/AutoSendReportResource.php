<?php

namespace App\Filament\Resources\AutoSendReports;

use App\Filament\Resources\AutoSendReports\Pages\CreateAutoSendReport;
use App\Filament\Resources\AutoSendReports\Pages\EditAutoSendReport;
use App\Filament\Resources\AutoSendReports\Pages\ListAutoSendReports;
use App\Filament\Resources\AutoSendReports\Pages\ViewAutoSendReport;
use App\Filament\Resources\AutoSendReports\Schemas\AutoSendReportForm;
use App\Filament\Resources\AutoSendReports\Schemas\AutoSendReportInfolist;
use App\Filament\Resources\AutoSendReports\Tables\AutoSendReportsTable;
use App\Models\AutoSendReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class AutoSendReportResource extends Resource
{
    protected static ?string $model = AutoSendReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentArrowUp;

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): string
    {
        return __('menu.operation_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.operationManagement.manage_auto_send_reports');
    }

    public static function form(Schema $schema): Schema
    {
        return AutoSendReportForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AutoSendReportInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AutoSendReportsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAutoSendReports::route('/'),
            'create' => CreateAutoSendReport::route('/create'),
            'view' => ViewAutoSendReport::route('/{record}'),
            'edit' => EditAutoSendReport::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
            return $query;
        }

        if ($user->hasAnyRole(['Property Management'])) {
            $residence = $user->propertyManagement;

            return $query->where('residence_id', $residence->id);
        }

        return $query;
    }
}
