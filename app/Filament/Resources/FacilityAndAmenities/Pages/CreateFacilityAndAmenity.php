<?php

namespace App\Filament\Resources\FacilityAndAmenities\Pages;

use App\Filament\Resources\FacilityAndAmenities\FacilityAndAmenityResource;
use App\Models\ClaimableTitle;
use Filament\Resources\Pages\CreateRecord;

class CreateFacilityAndAmenity extends CreateRecord
{
    protected static string $resource = FacilityAndAmenityResource::class;

    protected function afterCreate(): void
    {
        $data = $this->form->getState();
        $record = $this->record;

        foreach (ClaimableTitle::all() as $title) {
            $key = "claimable_title_{$title->id}";
            if (! empty($data[$key])) {
                $record->claimableTitles()->create([
                    'claimable_title_id' => $title->id,
                ]);
            }
        }
    }
}
