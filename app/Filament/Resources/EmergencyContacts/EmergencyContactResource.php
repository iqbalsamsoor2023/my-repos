<?php

namespace App\Filament\Resources\EmergencyContacts;

use App\Filament\Resources\EmergencyContacts\Pages\CreateEmergencyContact;
use App\Filament\Resources\EmergencyContacts\Pages\EditEmergencyContact;
use App\Filament\Resources\EmergencyContacts\Pages\ListEmergencyContacts;
use App\Filament\Resources\EmergencyContacts\Schemas\EmergencyContactForm;
use App\Filament\Resources\EmergencyContacts\Tables\EmergencyContactsTable;
use App\Models\EmergencyContact;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmergencyContactResource extends Resource
{
    protected static ?string $model = EmergencyContact::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoneArrowUpRight;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string
    {
        return __('menu.administrator_management');
    }

    public static function getModelLabel(): string
    {
        return __('user.emergency_contact');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.administratorManagement.manage_emergency_contacts');
    }

    public static function form(Schema $schema): Schema
    {
        return EmergencyContactForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmergencyContactsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmergencyContacts::route('/'),
            'create' => CreateEmergencyContact::route('/create'),
            'edit' => EditEmergencyContact::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return EmergencyContact::with(['districtEmergencyContacts.thailandDistrict']);
    }
}
