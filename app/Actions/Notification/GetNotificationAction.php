<?php

namespace App\Actions\Notification;

use App\Models\Notification;
use Illuminate\Http\Request;

class GetNotificationAction
{
    public function execute(Request $request)
    {
        $notification = new Notification;

        if (isset($request->notifiable_id)) {
            $notification = $notification->where('notifiable_id', $request->notifiable_id);
        }

        if (isset($request->notifiable_type)) {
            $notification = $notification->where('notifiable_type', 'LIKE', '%'.$request->notifiable_type.'%');
        }

        if (isset($request->type)) {
            $notification = $notification->where('type', '!=', $request->type);
        }

        if (isset($request->read_at)) {
            $notification = $notification->whereNull('read_at');
        }

        if (isset($request->model_type) && isset($request->model_id)) {
            $notification = $notification->whereRaw("JSON_UNQUOTE(data->'$.model_type') = ?", [$request->model_type])
                ->where('data->model_id', $request->model_id);
        }

        if (isset($request->has_pagination) && ($request->has_pagination == false)) {
            return $notification->orderBy('created_at', 'DESC')->get();
        }

        return $notification->orderBy('created_at', 'DESC')->paginate(20);
    }
}
