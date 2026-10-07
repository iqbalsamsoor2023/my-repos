<?php

namespace App\Filament\Resources\PrivateMaintenances\Pages;

use Filament\Actions\DeleteAction;
use App\Actions\Maintenance\SendMaintenanceUpdateNotification;
use App\Filament\Resources\PrivateMaintenances\PrivateMaintenanceResource;
use App\Models\Amenity;
use App\Models\Unit;
use Filament\Resources\Pages\EditRecord;

class EditPrivateMaintenance extends EditRecord
{
    protected static string $resource = PrivateMaintenanceResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_private_maintenance');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
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

        if ($amenityId) {
            if (!empty($this->record->claimable_item_details) && is_null($this->record->other_private_claim_item)) {
                $itemDetails = $this->record->claimable_item_details;

                $data['other_private_claim_item'] = $itemDetails['amenity_name'];      
            }

            // $amenity = Amenity::find($amenityId);

            // if (!empty($this->record->claimable_item_details)) {
            //     $itemDetails = $this->record->claimable_item_details;

            //     if ($amenity?->amenity_name === 'Others' || $itemDetails['amenity_name'] === 'Others') {
            //         $data['amenity'] = 'Others';
            //         $data['amenity_name'] = $this->record->miscellaneous ?? $itemDetails['amenity_name'];
            //     } elseif ($amenity) {
            //         $data['amenity'] = $amenity->id;
            //     } else {
            //         // DB record missing, fallback
            //         $data['amenity'] = $itemDetails['id'];
            //         $data['amenity_name'] = $itemDetails['amenity_name'];
            //     }
            // }
        }

        $data['maintainable_id'] = $unit->id ?? null;
        $data['residence_id'] = $unit->residence_id ?? null;
        $data['status'] = $this->record->getRawOriginal('status');
        $data['is_verified'] = $this->record->getRawOriginal('is_verified');

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $amenityValue = $data['amenity'] ?? null;

        if (empty($amenityValue)) {
            return $data;
        }

        if ($amenityValue !== 'Others') {
            $amenity = Amenity::find($amenityValue);
        } else {
            $amenity = Amenity::firstWhere('amenity_name', 'Others');
            $data['miscellaneous'] = $data['amenity_name'] ?? null;
        }

        if (! $amenity) {
            return $data;
        }

        $data['claimable_item_details'] = [
            'id' => $amenity->id,
            'amenity_name' => $amenity->amenity_name,
            'warranty_period' => $amenity->warranty_period,
            'period_type' => $amenity->period_type,
            'supplier' => $amenity->supplier,
            'is_out_warranty' => $amenity->is_out_warranty,
            'remark' => $amenity->remark,
        ];

        return $data;
    }

    protected function afterSave(): void
    {
        $sendMaintenanceUpdateNotification = new SendMaintenanceUpdateNotification;
        $sendMaintenanceUpdateNotification->execute($this->record);
    }
}
