<?php

namespace App\Actions\Event;

use App\Exceptions\GeneralException;
use App\Http\Requests\Event\StoreRsvpRequest;
use App\Models\EventRsvp;
use Illuminate\Http\JsonResponse;

class CreateRsvpAction
{
    public function execute(StoreRsvpRequest $request, int $id)
    {
        $request->merge([
            'event_id' => $id,
        ]);

        $rsvp = EventRsvp::updateOrCreate([
            'event_id' => $request->event_id,
            'user_id' => $request->user_id, ],
            $request->only([
                'event_id',
                'user_id',
                'is_going',
            ]));

        if (! $rsvp) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed creating rsvp');
        }

        return $rsvp;
    }
}
