<?php

namespace App\Traits;

use App\Support\DdiWidgetSupport;
use Livewire\Attributes\On;

trait DdiFiltersProvinceAndDistrict
{
    public ?array $provinceIds = [];

    public ?array $districtIds = [];

    #[On('ddi-filters-updated')]
    public function applyFilters(array $filters): void
    {
        $this->provinceIds = DdiWidgetSupport::normalizeIds($filters['province_ids'] ?? []);
        $this->districtIds = DdiWidgetSupport::normalizeIds($filters['district_ids'] ?? []);

        $this->dispatch('$refresh');
    }
}
