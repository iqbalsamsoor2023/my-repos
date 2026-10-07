<?php

namespace App\Listeners;

use OwenIt\Auditing\Events\Auditing;

class PreventApiAuditing
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param Auditing $event
     * @return bool
     */
    public function handle(Auditing $event)
    {
        // Prevent auditing on API requests
        if (request()->is('api/*')) {
            return false; // cancel audit
        }

        if (isset($event->model->remember_token)) {
            return false; // no need to record remember_token audit
        }

        return true; // allow audit
    }
}
