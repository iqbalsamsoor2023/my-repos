<?php

namespace App\Filament\Resources\HomeAppliances;

use App\Enums\User\RoleType;
use App\Filament\Resources\HomeAppliances\Pages\CreateHomeAppliance;
use App\Filament\Resources\HomeAppliances\Pages\EditHomeAppliance;
use App\Filament\Resources\HomeAppliances\Pages\ListHomeAppliances;
use App\Filament\Resources\HomeAppliances\Schemas\HomeApplianceForm;
use App\Filament\Resources\HomeAppliances\Tables\HomeAppliancesTable;
use App\Models\HouseholdItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HomeApplianceResource extends Resource
{
    protected static ?string $model = HouseholdItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): string
    {
        return __('menu.setting_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.home_appliances');
    }

    public static function getModelLabel(): string
    {
        return __('resales-and-tenancy.home_appliance');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasAnyRole([RoleType::SUPER_ADMIN->value]);
    }

    public static function form(Schema $schema): Schema
    {
        return HomeApplianceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HomeAppliancesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHomeAppliances::route('/'),
            'create' => CreateHomeAppliance::route('/create'),
            'edit' => EditHomeAppliance::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->homeAppliance();
    }
}
