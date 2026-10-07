<?php

namespace App\Filament\Resources\ResaleManagements\Pages;

use App\Enums\User\RoleType;
use App\Filament\Resources\ResaleManagements\ResaleManagementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateResaleManagement extends CreateRecord
{
    protected static string $resource = ResaleManagementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        $userHasRtmRole = $user->hasAnyRole([RoleType::RESALES_AND_TENANCY_MANAGEMENT->value]);

        if ($userHasRtmRole) {
            $data['residence_id'] = $userHasRtmRole ? get_residence_id_list_by_rtm($user->id)[0] : null;
        }

        return $data;
    }
}
