<?php

namespace App\Http\Resources\Comment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'commentable_type' => $this->commentable_type,
            'commentable_id' => $this->commentable_id,
            'content' => $this->content,
            'image_url' => $this->image_url,
            'created_at' => optional($this->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($this->updated_at)->format('Y-m-d H:i:s'),
            'user' => [
                'id' => $this?->user?->id,
                'name' => $this?->user?->name,
                'profile_image_url' => $this?->user?->profile_image_url,
            ],
        ];
    }
}
