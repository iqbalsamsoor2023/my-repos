<?php

namespace App\Filament\Resources\PrivateClaimItemTitles\Pages;

use App\Filament\Resources\PrivateClaimItemTitles\PrivateClaimItemTitleResource;
use App\Models\PrivateClaimItemOption;
use App\Models\PrivateClaimItemTitle;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPrivateClaimItemTitle extends EditRecord
{
    protected static string $resource = PrivateClaimItemTitleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $itemId = $this->record->private_claim_item_id;

        $selectedOptionIds = PrivateClaimItemTitle::where('private_claim_item_id', $itemId)
            ->pluck('private_claim_item_option_id')
            ->toArray();

        $data['options'] = $selectedOptionIds;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $itemId = $record->private_claim_item_id;

        $optionIds = $data['options'] ?? [];

        // delete old rows
        PrivateClaimItemTitle::where('private_claim_item_id', $itemId)->delete();

        // recreate new selection
        foreach ($optionIds as $optionId) {

            $option = PrivateClaimItemOption::find($optionId);

            if (!$option) continue;

            PrivateClaimItemTitle::create([
                'private_claim_item_id' => $itemId,
                'private_claim_item_option_id' => $option->id,
                'option_name' => $option->name,
                'option_name_th' => $option->name_th,
            ]);
        }

        return $record;
    }
}
