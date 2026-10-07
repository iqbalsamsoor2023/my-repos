<?php

namespace App\Filament\Resources\PublicMaintenances\Pages;

use App\Filament\Resources\PublicMaintenances\PublicMaintenanceResource;
use App\Models\ResidenceAmenity;
use App\Models\ResidenceAmenityOption;
use Filament\Resources\Pages\ViewRecord;

class ViewPublicMaintenance extends ViewRecord
{
    protected static string $resource = PublicMaintenanceResource::class;

    protected static ?string $title = 'View Public Maintenance';

    protected function canEdit(): bool
    {
        return false;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $type = $data['maintainable_type'] ?? null;
        $id = $data['maintainable_id'] ?? null;

        if ($type === ResidenceAmenityOption::class) {
            $data['maintainable_id'] = 'option-'.$id;
        } elseif ($type === ResidenceAmenity::class) {
            $data['maintainable_id'] = 'amenity-'.$id;
        }

        if ($type === ResidenceAmenityOption::class) {
            $facility = ResidenceAmenityOption::with('residenceAmenity')->find($id);
            $data['residence_id'] = optional($facility->residenceAmenity)->residence_id;
        } elseif ($type === ResidenceAmenity::class) {
            $facility = ResidenceAmenity::find($id);
            $data['residence_id'] = optional($facility)->residence_id;
        }

        $data['status'] = $this->record->getAttributes()['status'];
        $data['is_verified'] = $this->record->getAttributes()['is_verified'];
        $data['claimable_title_id'] = $data['claimable_item_details']['id'] ?? null;

        return $data;
    }
}
