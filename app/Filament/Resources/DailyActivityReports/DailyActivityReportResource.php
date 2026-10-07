<?php

namespace App\Filament\Resources\DailyActivityReports;

use App\Enums\User\RoleType;
use App\Filament\Resources\DailyActivityReports\Pages\ListDailyActivityReports;
use App\Filament\Resources\DailyActivityReports\Pages\ViewDailyActivityReport;
use App\Filament\Resources\DailyActivityReports\Schemas\DailyActivityReportForm;
use App\Filament\Resources\DailyActivityReports\Schemas\DailyActivityReportInfolist;
use App\Filament\Resources\DailyActivityReports\Tables\DailyActivityReportsTable;
use App\Models\Sgoc\DailyActivityReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DailyActivityReportResource extends Resource
{
    protected static ?string $model = DailyActivityReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static ?string $recordTitleAttribute = 'reported_by_name';

    protected static ?int $navigationSort = 7;

    public static function getNavigationGroup(): string
    {
        return __('menu.security_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.securityManagement.manage_daily_activity_reports_dar');
    }

    public static function infolist(Schema $schema): Schema
    {
        return DailyActivityReportInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DailyActivityReportsTable::configure($table);
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
            'index' => ListDailyActivityReports::route('/'),
            'view' => ViewDailyActivityReport::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery()
            ->with([
                'residence:id,name,name_th',
                'reportedBy:id,email',
                'shiftType:id,shift_name,shift_name_th',
                'reportCategoryItem.reportCategory:id,name,name_in_thai'
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
