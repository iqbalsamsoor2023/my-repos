<?php

namespace App\Filament\Resources\IncidentReports;

use App\Enums\User\RoleType;
use App\Filament\Resources\IncidentReports\Pages\DailyReports;
use App\Filament\Resources\IncidentReports\Pages\ListIncidentReports;
use App\Filament\Resources\IncidentReports\Pages\ViewIncidentReport;
use App\Filament\Resources\IncidentReports\Schemas\IncidentReportInfolist;
use App\Filament\Resources\IncidentReports\Tables\IncidentReportsTable;
use App\Models\IncidentReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class IncidentReportResource extends Resource
{
    protected static ?string $model = IncidentReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): string
    {
        return __('menu.security_management');
    }

    public static function getModelLabel(): string
    {
        return __('menu.incident_reports');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.incident_reports');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.securityManagement.manage_incident_reports_irs');
    }

    public static function getNavigationItems(): array
    {
        return array_merge(
            parent::getNavigationItems(),
            DailyReports::getNavigationItems(),
        );
    }

    public static function infolist(Schema $schema): Schema
    {
        return IncidentReportInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IncidentReportsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'daily-reports' => DailyReports::route('/daily-reports'),
            'index' => ListIncidentReports::route('/'),
            'view' => ViewIncidentReport::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery()
            ->with([
                'residence:id,name,name_th',
                'createdBy:id,name',
                'unit:id,unit_number',
            ]);

        if ($user->hasRole(RoleType::PROPERTY_MANAGEMENT->value)) {
            $query->whereHas('residence', function (Builder $q) use ($user) {
                $q->where('property_management_user_id', $user->id);
            });
        } elseif ($user->hasRole(RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value)) {
            $residenceIds = get_residence_by_property_management_operation_center($user->id);

            $query->whereIn('mmb_residence_id', $residenceIds);
        }

        return $query;
    }
}
