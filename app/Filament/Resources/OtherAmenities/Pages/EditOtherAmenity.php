<?php

namespace App\Filament\Resources\OtherAmenities\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\OtherAmenities\OtherAmenityResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOtherAmenity extends EditRecord
{
    protected static string $resource = OtherAmenityResource::class;

    public function getTitle(): string
    {
        return __('Edit Warranty');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
