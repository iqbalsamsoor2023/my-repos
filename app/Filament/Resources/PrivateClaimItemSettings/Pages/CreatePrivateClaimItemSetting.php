<?php

namespace App\Filament\Resources\PrivateClaimItemSettings\Pages;

use App\Filament\Resources\PrivateClaimItemSettings\PrivateClaimItemSettingResource;
use App\Models\PrivateClaimItem;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Builder;

class CreatePrivateClaimItemSetting extends CreateRecord
{
    protected static string $resource = PrivateClaimItemSettingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $privateClaimItemDetails = PrivateClaimItem::find($data['private_claim_item_id']);

        $data['private_claim_item_details'] = $privateClaimItemDetails;
        if (($data['period_type'] ?? null) === 'year') {
            // Convert years to months
            $data['warranty_period'] = (int) $data['warranty_period'] * 12;
        }

        $user = auth()->user();

        if ($user->hasRole('Property Management')) {
            $residence = get_residence_by_property_management($user->id);
            $data['residence_id'] = $residence->id;
        }

        return $data;
    }
}
