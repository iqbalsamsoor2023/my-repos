<?php

namespace App\Filament\Resources\PrivateClaimItemSettings\Pages;

use App\Filament\Resources\PrivateClaimItemSettings\PrivateClaimItemSettingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPrivateClaimItemSetting extends EditRecord
{
    protected static string $resource = PrivateClaimItemSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Turn on toggle if warranty exists
        if (!empty($data['warranty_period'])) {
            $data['has_warranty'] = true;

            // Convert months → years if divisible by 12
            if ($data['warranty_period'] >= 12 && $data['warranty_period'] % 12 === 0) {
                $data['period_type'] = 'year';
                $data['warranty_period'] = $data['warranty_period'] / 12;
            } else {
                $data['period_type'] = 'month';
            }
        } else {
            $data['has_warranty'] = false;
        }

        return $data;
    }
}
