<?php

namespace App\Filament\Resources\ResidenceActivationStatuses;

use App\Filament\Resources\ResidenceActivationStatuses\Pages\ListResidenceActivationStatuses;
use App\Filament\Resources\ResidenceActivationStatuses\Schemas\ResidenceActivationStatusForm;
use App\Filament\Resources\ResidenceActivationStatuses\Tables\ResidenceActivationStatusesTable;
use App\Models\ResidenceActivationStatus;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ResidenceActivationStatusResource extends Resource
{
    protected static ?string $model = ResidenceActivationStatus::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 13;

    protected static ?string $slug = 'residence-activation-status';

    public static function getNavigationGroup(): string
    {
        return __('menu.setting_management');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasRole('Super Admin');
    }

    public static function canAccess(): bool
    {
        if (auth()->user()->hasAnyRole(['Super Admin'])) {
            return true;
        }

        return false;
    }

    public static function getModelLabel(): string
    {
        return __('residence.residence_activation_status');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.residence_activation_statuses');
    }

    public static function getNavigationLabel(): string
    {
        return __('residence.residence_activation_settings');
    }

    public static function form(Schema $schema): Schema
    {
        return ResidenceActivationStatusForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ResidenceActivationStatusesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListResidenceActivationStatuses::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
