<?php

namespace App\Filament\Resources\Events\RelationManagers;

use Filament\Schemas\Schema;
use App\Filament\Resources\EventRsvps\EventRsvpResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class RsvpsRelationManager extends RelationManager
{
    protected static string $relationship = 'rsvps';

    protected static ?string $recordTitleAttribute = 'event_id';

    public function form(Schema $schema): Schema
    {
        return EventRsvpResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return EventRsvpResource::table($table);
    }
}
