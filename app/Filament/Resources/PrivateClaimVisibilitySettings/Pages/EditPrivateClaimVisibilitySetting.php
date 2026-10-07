<?php

namespace App\Filament\Resources\PrivateClaimVisibilitySettings\Pages;

use App\Filament\Resources\PrivateClaimVisibilitySettings\PrivateClaimVisibilitySettingResource;
use App\Models\PrivateClaimVisibilitySetting;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditPrivateClaimVisibilitySetting extends EditRecord
{
    protected static string $resource = PrivateClaimVisibilitySettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate($record, array $data): Model
    {
        DB::transaction(function () use ($record, $data) {
            $residenceId = $record->id;

            $items = $this->form->getState()['items'] ?? [];

            foreach ($items as $itemId => $isEnabled) {

                PrivateClaimVisibilitySetting::updateOrCreate(
                    [
                        'residence_id' => $residenceId,
                        'private_claim_item_id' => $itemId,
                    ],
                    [
                        'is_enabled' => $isEnabled,
                    ]
                );
            }
        });

        return $record;
    }
}
