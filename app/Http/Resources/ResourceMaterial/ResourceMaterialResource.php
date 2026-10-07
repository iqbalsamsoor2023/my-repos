<?php

namespace App\Http\Resources\ResourceMaterial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResourceMaterialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Get preferred language from Accept-Language header
        $lang = $request->header('Accept-Language', 'en');
        $lang = strtolower($lang);

        return [
            'id' => $this->id,
            'platform_id' => $this->platform_id,
            'platform_name' => $this->platform?->name ?? '-',
            'title' => $this->title,
            'tutorial_url' => $this->source_link,
        ];
    }
}
