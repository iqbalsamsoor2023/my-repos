<?php

namespace App\Filament\Resources\SosManagements;

use App\Filament\Resources\SosManagements\Pages\ListSosManagement;
use App\Filament\Resources\SosManagements\Tables\SosManagementsTable;
use App\Models\SosManagement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SosManagementResource extends Resource
{
    protected static ?string $model = SosManagement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?int $navigationSort = 8;

    public static function getNavigationGroup(): string
    {
        return __('menu.security_management');
    }

    public static function getModelLabel(): string
    {
        return __('menu.securityManagement.manage_sos');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.securityManagement.manage_sos');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.securityManagement.manage_sos');
    }

    public static function table(Table $table): Table
    {
        return SosManagementsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSosManagement::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = SosManagement::with(['unit.residence', 'createdBy']);
        $user = auth()->user();

        if ($user->hasRole('Property Management')) {
            $residence = get_residence_by_property_management($user->id);

            $query->whereHas('unit', function ($subQuery) use ($residence) {
                $subQuery->where('residence_id', $residence->id);
            });
        } elseif ($user->hasRole('Property Management Operation Center')) {
            $residence_ids = get_residence_by_property_management_operation_center($user->id);

            $query->whereHas('unit', function ($subQuery) use ($residence_ids) {
                $subQuery->whereIn('residence_id', $residence_ids);
            });
        }

        return $query;
    }
}
