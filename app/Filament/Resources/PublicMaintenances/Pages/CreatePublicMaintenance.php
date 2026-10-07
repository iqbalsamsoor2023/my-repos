<?php

namespace App\Filament\Resources\PublicMaintenances\Pages;

use App\Actions\Maintenance\SendMaintenanceCreateNotification;
use App\Filament\Resources\PublicMaintenances\PublicMaintenanceResource;
use App\Models\ClaimableTitle;
use App\Models\ResidenceAmenity;
use App\Models\ResidenceAmenityOption;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreatePublicMaintenance extends CreateRecord
{
    protected static string $resource = PublicMaintenanceResource::class;

    protected static ?string $title = 'Create Public Maintenance';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (array_key_exists('claimable_title_id', $data) && $data['claimable_title_id']) {
            $claimableTitle = ClaimableTitle::find($data['claimable_title_id']);

            if ($claimableTitle) {
                $data['claimable_item_details'] = [
                    'id' => $claimableTitle->id,
                    'claimable_title_name' => $claimableTitle->name,
                    'claimable_title_name_th' => $claimableTitle->name_in_thai,
                ];
            }
        }

        if ($data['maintainable_type'] === ResidenceAmenityOption::class) {
            $optionId = (int) Str::after($data['maintainable_id'], 'option-');
            $claimableItem = ResidenceAmenityOption::find($optionId);
        } elseif ($data['maintainable_type'] === ResidenceAmenity::class) {
            $amenityId = (int) Str::after($data['maintainable_id'], 'amenity-');
            $claimableItem = ResidenceAmenity::find($amenityId);
        }

        $data['reported_by'] = auth()->user()->id;
        $data['maintainable_id'] = $claimableItem->id;

        return $data;
    }

    protected function afterCreate(): void
    {
        $sendMaintenanceCreateNotification = new SendMaintenanceCreateNotification;
        $sendMaintenanceCreateNotification->execute($this->record);
    }
}
