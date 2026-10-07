<?php

namespace App\Http\Resources\IncidentReport;

use Illuminate\Http\Resources\Json\JsonResource;

class IncidentReportResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this['id'],
            'title' => $this['title'],
            'description' => $this['description'],
            'image_url' => $this['image_url'],
            'created_at' => $this['created_at'],
            'unit' => [
                'unit_number' => $this['unit']['unit_number'] ?? null,
            ],
        ];
    }
}
