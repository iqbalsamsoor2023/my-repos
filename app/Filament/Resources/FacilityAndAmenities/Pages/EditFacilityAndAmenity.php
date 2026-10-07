<?php

namespace App\Filament\Resources\FacilityAndAmenities\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\FacilityAndAmenities\FacilityAndAmenityResource;
use App\Models\ClaimableTitle;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditFacilityAndAmenity extends EditRecord
{
    protected static string $resource = FacilityAndAmenityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate($record, array $data): Model
    {
        $updated = parent::handleRecordUpdate($record, $data);

        $this->syncAmenityComponents($updated, $data);

        return $updated;
    }

    protected function syncAmenityComponents($record, array $data): void
    {
        $formState = $this->form->getState();

        $existingIds = $record->claimableTitles()->pluck('claimable_title_id')->toArray();

        foreach (ClaimableTitle::all() as $title) {
            $key = "claimable_title_{$title->id}";
            $isChecked = ! empty($formState[$key]);

            $exists = in_array($title->id, $existingIds);

            if ($isChecked && ! $exists) {
                $record->claimableTitles()->attach($title->id);
            }

            if (! $isChecked && $exists) {
                $record->claimableTitles()->detach($title->id);
            }
        }
    }
}
