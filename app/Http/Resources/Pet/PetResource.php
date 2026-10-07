<?php

namespace App\Http\Resources\Pet;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'unit_id' => (int) $this->unit_id,
            'user_id' => (int) $this->user_id,
            'breed' => $this->breed,
            'type' => $this->type,
            'year' => $this->year,
            'image_front_url' => $this->image_front_url,
            'image_back_url' => $this->image_back_url,
            'image_top_url' => $this->image_top_url,
            'image_bottom_url' => $this->image_bottom_url,
            'image_left_url' => $this->image_left_url,
            'image_right_url' => $this->image_right_url,
            'user' => [
                'name' => $this?->user?->name,
            ],
        ];
    }
}
