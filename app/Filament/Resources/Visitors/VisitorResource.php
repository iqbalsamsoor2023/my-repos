<?php

namespace App\Filament\Resources\Visitors;

use App\Filament\Resources\Visitors\Pages\DailyReports;
use App\Filament\Resources\Visitors\Pages\ListHistoricalVisitors;
use App\Filament\Resources\Visitors\Pages\ListVisitors;
use App\Filament\Resources\Visitors\Pages\ViewVisitor;
use App\Filament\Resources\Visitors\Schemas\VisitorInfolist;
use App\Filament\Resources\Visitors\Tables\VisitorsTable;
use App\Models\VisitorLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VisitorResource extends Resource
{
    protected static ?string $model = VisitorLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'visitors';

    protected static ?string $label = 'Visitors';

    public static function getNavigationGroup(): string
    {
        return __('menu.security_management');
    }

    public static function getModelLabel(): string
    {
        return __('visitor.visitor');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.securityManagement.manage_visitors');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.securityManagement.manage_visitors');
    }

    public static function getNavigationItems(): array
    {
        return array_merge(
            parent::getNavigationItems(),
            DailyReports::getNavigationItems(),
        );
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return VisitorInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VisitorsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'daily-reports' => DailyReports::route('/daily-reports'),
            'historical' => ListHistoricalVisitors::route('/historical'),
            'index' => ListVisitors::route('/'),
            'view' => ViewVisitor::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = VisitorLog::query();

        if ($user->hasAnyRole(['Property Management', 'Property Management Operation Center'])) {
            $residenceIds = $user->hasRole('Property Management')
                ? [get_residence_by_property_management($user->id)->id]
                : get_residence_by_property_management_operation_center($user->id);

            $query->whereIn('residence_id', $residenceIds);
        } elseif ($user->hasRole('Developer')) {
            $residenceIds = get_residence_by_developer($user->id);

            $query->whereIn('residence_id', $residenceIds);
        }

        return $query;
    }
}
