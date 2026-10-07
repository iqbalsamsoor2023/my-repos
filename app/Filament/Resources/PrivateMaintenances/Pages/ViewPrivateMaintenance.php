<?php

namespace App\Filament\Resources\PrivateMaintenances\Pages;

use App\Filament\Resources\PrivateMaintenances\PrivateMaintenanceResource;
use App\Models\Amenity;
use App\Models\Unit;
use Filament\Resources\Pages\ViewRecord;

class ViewPrivateMaintenance extends ViewRecord
{
    protected static string $resource = PrivateMaintenanceResource::class;

    protected static ?string $title = 'View Private Maintenance';

    protected function canEdit(): bool
    {
        return false;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $maintainable = $this->record->maintainable;

        if ($maintainable instanceof Unit) {
            $unit = $maintainable;
        } else {
            $unit = Unit::find($data['maintainable_id']);
        }

        // Preload amenity in bulk to avoid multiple queries
        $amenityId = $this->record->claimable_item_details['id'] ?? null;
        $amenity = $amenityId ? Amenity::find($amenityId) : null;

        if (isset($this->record->claimable_item_details)) {
            $itemDetails = $this->record->claimable_item_details;

            if ($amenity?->amenity_name === 'Others' || $itemDetails['amenity_name'] === 'Others') {
                $data['amenity'] = 'Others';
                $data['amenity_name'] = $this->record->miscellaneous ?? $itemDetails['amenity_name'];
            } elseif ($amenity) {
                $data['amenity'] = $amenity->id;
            } else {
                // DB record missing, fallback
                $data['amenity'] = $itemDetails['id'];
                $data['amenity_name'] = $itemDetails['amenity_name'];
            }
        }

        $data['maintainable_id'] = $unit->id ?? null;
        $data['residence_id'] = $unit->residence_id ?? null;
        $data['status'] = $this->record->getRawOriginal('status');
        $data['is_verified'] = $this->record->getRawOriginal('is_verified');
        
        return $data;
    }
}
