<?php

namespace App\Filament\Resources\PrivateClaimItemTitles\Pages;

use App\Filament\Resources\PrivateClaimItemTitles\PrivateClaimItemTitleResource;
use App\Models\PrivateClaimItemOption;
use App\Models\PrivateClaimItemTitle;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePrivateClaimItemTitle extends CreateRecord
{
    protected static string $resource = PrivateClaimItemTitleResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $optionIds = $data['options'] ?? []; // checkbox selections
        unset($data['options']);             // remove from main data

        $firstRecord = null;

        foreach ($optionIds as $optionId) {
            $option = PrivateClaimItemOption::find($optionId);

            if (!$option) continue;

            $record = PrivateClaimItemTitle::create([
                'private_claim_item_id' => $data['private_claim_item_id'],
                'private_claim_item_option_id' => $option->id,
                'option_name' => $option->name,
                'option_name_th' => $option->name_th,
            ]);

            if (!$firstRecord) {
                $firstRecord = $record; // Filament expects a single model return
            }
        }

        return $firstRecord;
    }
}
