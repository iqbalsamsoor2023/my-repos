<?php

namespace App\Filament\Resources\EventRsvps;

use App\Filament\Resources\EventRsvps\Pages\CreateEventRsvp;
use App\Filament\Resources\EventRsvps\Pages\EditEventRsvp;
use App\Filament\Resources\EventRsvps\Pages\ListEventRsvps;
use App\Filament\Resources\EventRsvps\Schemas\EventRsvpForm;
use App\Filament\Resources\EventRsvps\Tables\EventRsvpsTable;
use App\Models\EventRsvp;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class EventRsvpResource extends Resource
{
    protected static ?string $model = EventRsvp::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): string
    {
        return __('menu.operation_management');
    }

    public static function form(Schema $schema): Schema
    {
        return EventRsvpForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventRsvpsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEventRsvps::route('/'),
            'create' => CreateEventRsvp::route('/create'),
            'edit' => EditEventRsvp::route('/{record}/edit'),
        ];
    }
}
