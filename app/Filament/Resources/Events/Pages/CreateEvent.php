<?php

namespace App\Filament\Resources\Events\Pages;

use App\Actions\Event\CreateEventAction;
use App\Filament\Resources\Events\EventResource;
use App\Models\Event;
use Filament\Resources\Pages\CreateRecord;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        if ($this->record->is_active == true) {
            $this->toDatabase($this->record);
        }
    }

    public function toDatabase(Event $event)
    {
        $event_action = new CreateEventAction;

        return $event_action->sendEventCreatedNotification($event);
    }
}
