<?php

namespace App\Filament\Resources\LivingSpaces;

use App\Enums\User\RoleType;
use App\Filament\Resources\LivingSpaces\Pages\CreateLivingSpace;
use App\Filament\Resources\LivingSpaces\Pages\EditLivingSpace;
use App\Filament\Resources\LivingSpaces\Pages\ListLivingSpaces;
use App\Filament\Resources\LivingSpaces\Schemas\LivingSpaceForm;
use App\Filament\Resources\LivingSpaces\Tables\LivingSpacesTable;
use App\Models\HouseholdItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LivingSpaceResource extends Resource
{
    protected static ?string $model = HouseholdItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): string
    {
        return __('menu.setting_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.living_spaces');
    }

    public static function getModelLabel(): string
    {
        return __('resales-and-tenancy.living_space');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasAnyRole([RoleType::SUPER_ADMIN->value]);
    }

    public static function form(Schema $schema): Schema
    {
        return LivingSpaceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LivingSpacesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLivingSpaces::route('/'),
            'create' => CreateLivingSpace::route('/create'),
            'edit' => EditLivingSpace::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->livingSpace();
    }
}
