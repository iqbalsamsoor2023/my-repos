<?php

namespace App\Http\Resources\OtherAmenity;

use Illuminate\Http\Resources\Json\ResourceCollection;

class OtherAmenityCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'current_page' => $this->currentPage(),
            'data' => OtherAmenityResource::collection($this->collection),
            'first_page_url' => $this->url(1),
            'from' => $this->firstItem(),
            'last_page' => $this->lastPage(),
            'last_page_url' => $this->url($this->lastPage()),
            'links' => $this->linkCollection()->toArray(),
            'next_page_url' => $this->nextPageUrl(),
            'path' => $this->path(),
            'per_page' => $this->perPage(),
            'prev_page_url' => $this->previousPageUrl(),
            'to' => $this->lastItem(),
            'total' => $this->total(),
        ];
    }
}
