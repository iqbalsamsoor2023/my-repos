<?php

namespace App\Actions\Event;

use App\Exceptions\GeneralException;
use App\Http\Requests\Event\StoreEventRequest;
use App\Models\Event;
use App\Models\UnitUser;
use App\Models\User;
use App\Notifications\EventCreated;
use Filament\Notifications\Notification as FilamentsNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Notification;

class CreateEventAction
{
    public function execute(StoreEventRequest $request)
    {
        $event = Event::create($request->only([
            'residence_id',
            'title',
            'description',
            'start_at',
            'end_at',
            'is_active',
            'is_cancel',
            'created_by',
        ]));

        if (! $event) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed creating event');
        }

        $this->uploadEventImage($event, $request);
        $this->sendEventCreatedNotification($event);

        return $event;
    }

    private function uploadEventImage($event, $request)
    {
        if ($request->hasFile('images')) {
            $event->addMultipleMediaFromRequest(['images'])
                ->each(function ($fileAdded) {
                    $fileAdded->toMediaCollection('images');
                });
        }
    }

    public function sendEventCreatedNotification($event)
    {
        $unitUsers = UnitUser::whereHas('unit', function ($q) use ($event) {
            return $q->whereResidenceId($event->residence_id);
        })->get()->unique('user_id');

        foreach ($unitUsers as $unitUser) {
            Notification::send($unitUser->user, new EventCreated($event));
        }

        // send notification to PM dashboard
        $recipient = User::where('id', $event->residence->property_management_user_id)->first();

        if (App::getLocale() == 'th') {
            FilamentsNotification::make()
                ->title('Event Successfully created for '.$event->residence->name)
                ->sendToDatabase($recipient);
        } else {
            FilamentsNotification::make()
                ->title('Event Successfully created for '.$event->residence->name)
                ->sendToDatabase($recipient);
        }
    }
}
