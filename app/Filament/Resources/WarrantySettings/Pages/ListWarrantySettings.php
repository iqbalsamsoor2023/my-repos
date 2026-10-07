<?php

namespace App\Filament\Resources\WarrantySettings\Pages;

use App\Filament\Resources\WarrantySettings\WarrantySettingResource;
use App\Models\WarrantySetting;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Enums\User\RoleType;

class ListWarrantySettings extends ListRecords
{
    protected static string $resource = WarrantySettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->hidden(function () {
                    $user = auth()->user();

                    if (! $user->hasRole(RoleType::PROPERTY_MANAGEMENT->value)) {
                        return false;
                    }

                    $residence = get_residence_by_property_management($user->id);

                    if (! $residence) {
                        return true;
                    }

                    return WarrantySetting::where('residence_id', $residence->id)->exists();
                }),
        ];
    }
}
