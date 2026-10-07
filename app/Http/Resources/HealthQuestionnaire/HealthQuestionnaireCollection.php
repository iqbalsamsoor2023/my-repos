<?php

namespace App\Http\Resources\HealthQuestionnaire;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Http\Resources\Json\ResourceCollection;

class HealthQuestionnaireCollection extends ResourceCollection
{
    public function toArray($request)
    {
        if ($this->resource instanceof LengthAwarePaginator ||
            $this->resource instanceof Paginator) {
            return [
                'current_page' => $this->currentPage(),
                'data' => HealthQuestionnaireResource::collection($this->collection),
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

        // If not paginated, just return the data collection
        return $this->collection;
    }
}
