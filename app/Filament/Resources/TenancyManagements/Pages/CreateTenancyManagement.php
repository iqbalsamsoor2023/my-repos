<?php

namespace App\Filament\Resources\TenancyManagements\Pages;

use App\Enums\User\RoleType;
use App\Filament\Resources\TenancyManagements\TenancyManagementResource;
use App\Models\CustomRentalContract;
use Filament\Resources\Pages\CreateRecord;

class CreateTenancyManagement extends CreateRecord
{
    protected static string $resource = TenancyManagementResource::class;

    public function mutateFormDataBeforeCreate($data): array
    {
        $user = auth()->user();
        $userHasRtmRole = $user->hasAnyRole([RoleType::RESALES_AND_TENANCY_MANAGEMENT->value]);

        if ($userHasRtmRole) {
            $data['residence_id'] = $userHasRtmRole ? get_residence_id_list_by_rtm($user->id)[0] : null;
        }

        if ($data['has_custom_rule']) {
            $rentalRules = $data['custom_rental_contracts'];

            foreach ($rentalRules as $rentalRule) {
                CustomRentalContract::create([
                    'unit_id' => $data['unit_id'],
                    'contract_months' => $rentalRule['contract_months'],
                    'rent_price' => $rentalRule['rent_price'],
                    'deposit' => $rentalRule['deposit'],
                ]);
            }

            unset($data['custom_rental_contracts']);
        }

        return $data;
    }
}
