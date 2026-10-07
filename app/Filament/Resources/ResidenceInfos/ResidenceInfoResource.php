<?php

namespace App\Filament\Resources\ResidenceInfos;

use App\Enums\User\RoleType;
use App\Filament\Resources\ResidenceInfos\Pages\ListResidenceInfos;
use App\Filament\Resources\ResidenceInfos\Tables\ResidenceInfosTable;
use App\Models\Residence;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ResidenceInfoResource extends Resource
{
    protected static ?string $model = Residence::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string
    {
        return __('menu.big_data_management');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::auth()->user()?->hasAnyRole([RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value]) ?? false;
    }

    public static function canAccess(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public static function getModelLabel(): string
    {
        return __('menu.residence_info');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.residence_info');
    }

    public static function table(Table $table): Table
    {
        return ResidenceInfosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListResidenceInfos::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'propertyManagementUser',
            'subdistrict.district.province',
            'residenceFeatures',
        ]);
    }
}
