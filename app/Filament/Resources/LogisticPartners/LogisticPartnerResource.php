<?php

namespace App\Filament\Resources\LogisticPartners;

use Filament\Schemas\Schema;
use App\Filament\Resources\LogisticPartners\Pages\CreateLogisticPartner;
use App\Filament\Resources\LogisticPartners\Pages\EditLogisticPartner;
use App\Filament\Resources\LogisticPartners\Pages\ListLogisticPartners;
use App\Filament\Resources\LogisticPartners\Schemas\LogisticPartnerForm;
use App\Filament\Resources\LogisticPartners\Tables\LogisticPartnersTable;
use App\Models\LogisticPartner;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LogisticPartnerResource extends Resource
{
    protected static ?string $model = LogisticPartner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return LogisticPartnerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LogisticPartnersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLogisticPartners::route('/'),
            'create' => CreateLogisticPartner::route('/create'),
            'edit' => EditLogisticPartner::route('/{record}/edit'),
        ];
    }
}
