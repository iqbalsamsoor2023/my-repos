<?php

namespace App\Http\Resources\Event;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'residence_id' => $this->residence_id,
            'title' => $this->title,
            'description' => $this->description,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'is_active' => $this->is_active,
            'is_cancel' => $this->is_cancel,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'image_url' => $this->image_url,
            'rsvp_status' => $this->rsvp_status,
            'read_status' => $this->read_status,
            'user_reaction' => (int) $this->user_reaction,
            'user_like_count' => $this->user_like_count,
            'user_read_count' => $this->user_read_count,
            'created_at' => optional($this->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($this->updated_at)->format('Y-m-d H:i:s'),
            'rsvps' => $this->rsvps,
            'media' => $this->media,
        ];
    }
}
