<?php

namespace App\Filament\Resources\EventRsvps\Pages;

use Filament\Tables\Filters\TernaryFilter;
use App\Filament\Resources\EventRsvps\EventRsvpResource;
use App\Models\EventRsvp;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class ListEventRsvps extends ListRecords
{
    protected static string $resource = EventRsvpResource::class;

    protected function getTableQuery(): Builder
    {
        return EventRsvp::with('user');
    }

    public function getTableFilters(): array
    {
        return [
            TernaryFilter::make('is_going'),
        ];
    }
}
