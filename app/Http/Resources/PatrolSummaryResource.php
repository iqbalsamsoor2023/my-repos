<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class PatrolSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        $createdAt = new Carbon(data_get($this, 'created_at'));
        $updatedAt = new Carbon(data_get($this, 'updated_at'));

        return [
            'checkpoint_name' => data_get($this, 'checkpoint.name'),
            'staff_name' => data_get($this, 'user.name'),
            'questions' => data_get($this, 'questionnaires'),
            'image' => explode(',', $this->image_urls)[0],
            'images' => explode(',', $this->image_urls),
            'created_at' => $createdAt->format('d-m-Y h:i:s A'),
            'updated_at' => $updatedAt->format('d-m-Y h:i:s A'),
        ];
    }
}
