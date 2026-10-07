<?php

namespace App\Http\Resources;

use App\Models\Notification;
use Illuminate\Http\Resources\Json\JsonResource;

class AnnoucementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'residence_id' => (int) $this->residence_id,
            'title' => $this->title,
            'description' => $this->description,
            'image_urls' => $this->image_url != '' ? explode(',', $this->image_url) : [],
            'document_url' => $this->document_url,
            'is_active' => (int) $this->is_active,
            'created_by' => (int) $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'read_at_indicator' => $this->read_at_indicator ?? false,
            'user_like_count' => $this->user_like_count,
            'user_read_count' => $this->user_read_count,
            'user_reaction' => (int) $this->user_reaction,
        ];
    }
}
