<?php

namespace App\Filament\Resources\CheckPointLogs;

use App\Filament\Resources\CheckPointLogs\Pages\DailyReports;
use App\Filament\Resources\CheckPointLogs\Pages\ListCheckPointLogs;
use App\Filament\Resources\CheckPointLogs\Pages\ViewCheckPointLog;
use App\Filament\Resources\CheckPointLogs\Schemas\CheckPointLogInfolist;
use App\Filament\Resources\CheckPointLogs\Tables\CheckPointLogsTable;
use App\Models\CheckpointLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CheckPointLogResource extends Resource
{
    protected static ?string $model = CheckpointLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?int $navigationSort = 5;

    public static function getTitle(): string
    {
        return __('menu.patrol_checkpoints');
    }

    public static function getNavigationGroup(): string
    {
        return __('menu.security_management');
    }

    public static function getModelLabel(): string
    {
        return __('menu.patrol_checkpoints');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.patrol_checkpoints');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.securityManagement.manage_patrol_checkpoint_pgs');
    }

    public static function getNavigationItems(): array
    {
        return array_merge(
            parent::getNavigationItems(),
            DailyReports::getNavigationItems(),
        );
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return CheckPointLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CheckPointLogsTable::configure($table);
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
            'daily-reports' => DailyReports::route('/daily-reports'),
            'index' => ListCheckPointLogs::route('/'),
            'view' => ViewCheckPointLog::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery()->with([
            'checkpoint.residence',
            'user',
        ]);

        if ($user->hasRole('Property Management')) {
            $query->whereHas('checkpoint.residence', function (Builder $subQuery) use ($user) {
                $subQuery->where('property_management_user_id', $user->id);
            });
        } elseif ($user->hasRole('Property Management Operation Center')) {
            $residence_ids = get_residence_by_property_management_operation_center($user->id);

            $query->whereHas('checkpoint.residence', function (Builder $subQuery) use ($residence_ids) {
                $subQuery->whereIn('id', $residence_ids);
            });
        }

        return $query->orderByDesc('id');
    }
}
