<?php

namespace App\Filament\Resources\WarrantySettings;

use App\Filament\Resources\WarrantySettings\Pages\CreateWarrantySetting;
use App\Filament\Resources\WarrantySettings\Pages\EditWarrantySetting;
use App\Filament\Resources\WarrantySettings\Pages\ListWarrantySettings;
use App\Filament\Resources\WarrantySettings\Schemas\WarrantySettingForm;
use App\Filament\Resources\WarrantySettings\Tables\WarrantySettingsTable;
use App\Models\WarrantySetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WarrantySettingResource extends Resource
{
    protected static ?string $model = WarrantySetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 4;
    
    public static function getNavigationGroup(): string
    {
        return __('menu.maintenance_management');
    }

    public static function getModelLabel(): string
    {
        return __('maintenance.warranty_setting');
    }

    public static function getPluralModelLabel(): string
    {
        return __('maintenance.warranty_settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.maintenanceManagement.warranty_settings');
    }

    public static function form(Schema $schema): Schema
    {
        return WarrantySettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarrantySettingsTable::configure($table);
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
            'index' => ListWarrantySettings::route('/'),
            'create' => CreateWarrantySetting::route('/create'),
            'edit' => EditWarrantySetting::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery();

        if ($user->hasRole('Property Management')) {
            $residence = get_residence_by_property_management($user->id);
            if ($residence) {
                $query->where('residence_id', $residence->id);
            }
        }

        return $query;
    }
}
