<?php

namespace App\Http\Resources;

use App\Models\ResidenceAmenity;
use App\Models\ResidenceAmenityOption;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\DB;

class PublicClaimItemCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $lang = $request->header('Accept-Language');
        $isThai = strtolower($lang) === 'th';

        // Get all claimable title mappings grouped by facility_and_amenity_id
        $claimableMap = DB::table('claimable_title_facility_and_amenity')
            ->join('claimable_titles', 'claimable_titles.id', '=', 'claimable_title_facility_and_amenity.claimable_title_id')
            ->select(
                'claimable_title_facility_and_amenity.facility_and_amenity_id',
                'claimable_titles.id as claimable_title_id',
                DB::raw($isThai ? 'claimable_titles.name_in_thai as name' : 'claimable_titles.name as name')
            )
            ->get()
            ->groupBy('facility_and_amenity_id');

        $data = $this->collection->flatMap(function ($amenity) use ($isThai, $claimableMap) {
            $facility = $amenity->facilityAndAmenity;
            $facilityName = $facility
                ? ($isThai ? $facility->name_in_thai : $facility->name)
                : '-';

            // Claimable items for this facility
            $claimableItems = collect($claimableMap->get($facility->id, []))->map(function ($item) {
                return [
                    'claimable_title_id' => $item->claimable_title_id,
                    'claimable_title_name' => $item->name,
                ];
            })->values();

            if ($amenity->residenceAmenityOptions->isNotEmpty()) {
                return $amenity->residenceAmenityOptions->map(function ($option) use ($facilityName, $isThai, $amenity, $claimableItems) {
                    $optionName = $isThai ? $option->name_in_thai : $option->name;

                    return [
                        'id' => $option->id,
                        'residence_id' => $amenity->residence_id,
                        'name' => "{$facilityName} ({$optionName})",
                        'model_type' => ResidenceAmenityOption::class,
                        'claimable_items' => $claimableItems,
                    ];
                });
            }

            return [[
                'id' => $amenity->id,
                'residence_id' => $amenity->residence_id,
                'name' => $facilityName,
                'model_type' => ResidenceAmenity::class,
                'claimable_items' => $claimableItems,
            ]];
        })->values(); // ensure it's not a collection of collections

        return [
            'current_page' => $this->currentPage(),
            'data' => $data,
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
