<?php

namespace App\Http\Resources\Visitor;

use Illuminate\Http\Resources\Json\ResourceCollection;

class VisitorLogCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => VisitorLogResource::collection($this->collection),
            'total' => $this->resource->total(),
            'per_page' => $this->perPage(),
            'current_page' => $this->currentPage(),
            'last_page' => $this->resource->lastPage(),
            'from' => $this->firstItem(),
            'to' => $this->lastItem(),
        ];
    }
}
