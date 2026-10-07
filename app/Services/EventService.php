<?php

namespace App\Services;

use App\Actions\Event\CreateEventAction;
use App\Actions\Event\CreateRsvpAction;
use App\Actions\Event\GetEventAction;
use App\Actions\Event\GetOneEventAction;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\StoreRsvpRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class EventService
{
    public function index(Request $request)
    {
        $eventAction = new GetEventAction;
        $events = $eventAction->execute($request);

        return $events;
    }

    public function create(StoreEventRequest $request)
    {
        $eventAction = new CreateEventAction;
        $event = $eventAction->execute($request);

        return $event;
    }

    public function show(int $id): Model
    {
        $eventAction = new GetOneEventAction;
        $event = $eventAction->execute($id);

        return $event;
    }

    public function rsvp(StoreRsvpRequest $request, int $id)
    {
        $rsvpAction = new CreateRsvpAction;
        $rsvp = $rsvpAction->execute($request, $id);

        return $rsvp;
    }
}
