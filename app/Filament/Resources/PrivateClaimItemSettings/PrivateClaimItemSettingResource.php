<?php

namespace App\Filament\Resources\PrivateClaimItemSettings;

use App\Filament\Resources\PrivateClaimItemSettings\Pages\CreatePrivateClaimItemSetting;
use App\Filament\Resources\PrivateClaimItemSettings\Pages\EditPrivateClaimItemSetting;
use App\Filament\Resources\PrivateClaimItemSettings\Pages\ListPrivateClaimItemSettings;
use App\Filament\Resources\PrivateClaimItemSettings\Schemas\PrivateClaimItemSettingForm;
use App\Filament\Resources\PrivateClaimItemSettings\Tables\PrivateClaimItemSettingsTable;
use App\Models\PrivateClaimItemSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PrivateClaimItemSettingResource extends Resource
{
    protected static ?string $model = PrivateClaimItemSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string
    {
        return __('menu.maintenance_management');
    }

    public static function getModelLabel(): string
    {
        return __('maintenance.private_claim_item_setting');
    }

    public static function getPluralModelLabel(): string
    {
        return __('maintenance.private_claim_item_settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.maintenanceManagement.private_claim_item_settings');
    }

    public static function form(Schema $schema): Schema
    {
        return PrivateClaimItemSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PrivateClaimItemSettingsTable::configure($table);
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
            'index' => ListPrivateClaimItemSettings::route('/'),
            'create' => CreatePrivateClaimItemSetting::route('/create'),
            'edit' => EditPrivateClaimItemSetting::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery();

        if ($user->hasRole('Property Management')) {
            $query->whereHas('residence', function (Builder $q) use ($user) {
                $q->where('property_management_user_id', $user->id);
            });
        }

        return $query;
    }
}
