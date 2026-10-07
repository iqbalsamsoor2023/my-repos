<?php

namespace App\Filament\Resources\TenancyManagements\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\TenancyManagements\TenancyManagementResource;
use App\Models\CustomRentalContract;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenancyManagement extends EditRecord
{
    protected static string $resource = TenancyManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function mutateFormDataBeforeFill(array $data): array
    {
        $rentalRules = CustomRentalContract::where('unit_id', $data['unit_id'])->get();
        $data['custom_rental_contracts'] = $rentalRules;

        return $data;
    }

    public function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['has_custom_rule']) {
            $rentalRules = $data['custom_rental_contracts'];
            $rentalContractMonthList = array_column($rentalRules, 'contract_months');

            // Delete existing rental rules that are not present in the new data
            CustomRentalContract::where('unit_id', $data['unit_id'])
                ->whereNotIn('contract_months', $rentalContractMonthList)
                ->delete();

            // Update existing rental rules that are present in the new data
            foreach ($rentalRules as $rentalRule) {
                CustomRentalContract::updateOrCreate(
                    ['unit_id' => $data['unit_id'], 'contract_months' => $rentalRule['contract_months']],
                    ['rent_price' => $rentalRule['rent_price'], 'deposit' => $rentalRule['deposit']]
                );
            }

            unset($data['custom_rental_contracts']);
        } else {
            CustomRentalContract::where('unit_id', $data['unit_id'])->delete();
        }

        return $data;
    }
}
