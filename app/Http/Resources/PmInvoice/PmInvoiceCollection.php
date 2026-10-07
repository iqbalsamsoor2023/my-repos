<?php

namespace App\Http\Resources\PmInvoice;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PmInvoiceCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'current_page' => $this->currentPage(),
            'data' => PmInvoiceResource::collection($this->collection)->each(function ($resource) {
                return $resource->withoutBillings();
            }),
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
