<?php

namespace App\Filament\Resources\ResidenceBpos;

use App\Enums\Residence\MoobanType;
use App\Enums\User\RoleType;
use App\Filament\Resources\ResidenceBpos\Pages\ListResidenceBpos;
use App\Filament\Resources\ResidenceBpos\Tables\ResidenceBposTable;
use App\Models\Residence;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ResidenceBpoResource extends Resource
{
    protected static ?string $model = Residence::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): string
    {
        return __('menu.big_data_management');
    }

    public static function getModelLabel(): string
    {
        return __('menu.residence_bpo');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.residence_bpo');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::auth()->user()?->hasAnyRole([RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value]) ?? false;
    }

    public static function canAccess(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public static function table(Table $table): Table
    {
        return ResidenceBposTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListResidenceBpos::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->where('residences.mooban_type', MoobanType::PUBLIC->value)
            ->with([
                'developer',
                'propertyManagementUser',
                'propertyManagement',
                'sgocCompany',
                'subscriptionExpires',
                'subdistrict',
                'subdistrict.district',
                'subdistrict.district.province',
                'residenceFeatures',
            ]);

        $query->select('residences.*')
            ->leftJoin('residence_stats_view as rsv', 'rsv.residence_id', '=', 'residences.id')
            ->addSelect([
                'rsv.distinct_user_count',
                'rsv.sign_up_percentage',
                'rsv.units_count',
            ]);

        return $query;
    }
}
