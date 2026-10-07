<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class LogisticPartnerResource extends JsonResource
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
            'id' => data_get($this, 'id'),
            'category' => data_get($this, 'category'),
            'modes' => data_get($this, 'modes'),
            'name' => data_get($this, 'name'),
            'logo_url' => $this->logo_url,
            'created_at' => $createdAt->format('d-m-Y h:i:s A'),
            'updated_at' => $updatedAt->format('d-m-Y h:i:s A'),
        ];
    }
}
