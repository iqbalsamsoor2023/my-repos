<?php

namespace App\Filament\Resources\PrivateClaimVisibilitySettings;

use App\Filament\Resources\PrivateClaimVisibilitySettings\Pages\CreatePrivateClaimVisibilitySetting;
use App\Filament\Resources\PrivateClaimVisibilitySettings\Pages\EditPrivateClaimVisibilitySetting;
use App\Filament\Resources\PrivateClaimVisibilitySettings\Pages\ListPrivateClaimVisibilitySettings;
use App\Filament\Resources\PrivateClaimVisibilitySettings\Schemas\PrivateClaimVisibilitySettingForm;
use App\Filament\Resources\PrivateClaimVisibilitySettings\Tables\PrivateClaimVisibilitySettingsTable;
use App\Models\PrivateClaimVisibilitySetting;
use App\Models\Residence;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PrivateClaimVisibilitySettingResource extends Resource
{
    protected static ?string $model = PrivateClaimVisibilitySetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
   
    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string
    {
        return __('menu.maintenance_management');
    }
    
    public static function getModelLabel(): string
    {
        return __('maintenance.private_claim_visibility_setting');
    }

    public static function getPluralModelLabel(): string
    {
        return __('maintenance.private_claim_visibility_settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.settingManagement.private_claim_visibility_settings');
    }

    public static function form(Schema $schema): Schema
    {
        return PrivateClaimVisibilitySettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PrivateClaimVisibilitySettingsTable::configure($table);
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
            'index' => ListPrivateClaimVisibilitySettings::route('/'),
            'create' => CreatePrivateClaimVisibilitySetting::route('/create'),
            'edit' => EditPrivateClaimVisibilitySetting::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $residence = get_residence_by_property_management($user->id);
    
        $query = Residence::query();
    
        if ($residence) {
            $query->where('id', $residence->id);
        }
    
        return $query;
    }
}
