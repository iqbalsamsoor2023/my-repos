<?php

namespace App\Filament\Resources\Residences\Pages;

use App\Enums\Residence\Features;
use App\Filament\Resources\Residences\ResidenceResource;
use App\Helpers\Residence\AccountHelper;
use App\Models\Erp\CdpCompany;
use App\Models\ResidenceFeature;
use App\Models\SubscriptionExpire;
use App\Support\ResidenceFeatureSupport;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateResidence extends CreateRecord
{
    protected static string $resource = ResidenceResource::class;

    public function getTitle(): string
    {
        return __('menu.create_residence');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        if (! empty($data['developer_id'])) {
            $developerUserId = CdpCompany::whereId((int) $data['developer_id'])->value('mmb_user_id');
            $data['developer_user_id'] = $developerUserId;
        }

        return static::getModel()::create($data);
    }

    protected function afterCreate(): void
    {
        // Handle receptionist account creation
        // $this->createReceptionistAccount();

        // Create expiry dates for different features
        $this->createExpiryDates();

        // Process residence features
        foreach ($this->data['residenceFeatures'] as $key => $residenceFeature) {
            $this->createResidenceFeature($residenceFeature, $key, $this->record->id);
        }
    }

    // private function createReceptionistAccount()
    // {
    //     if (! empty($this->data['receptionist']) && $this->data['receptionist'] == GeneralStatus::ACTIVE->value) {
    //         $receptionistAccount = AutomationAccountGenerator::generate($this->record, RoleType::RC->value);
    //         $receptionistUser = User::create(AutomationAccountGenerator::manageAccount($receptionistAccount));
    //         AutomationAccountGenerator::attachRole($receptionistUser, RoleType::RECEPTIONIST->value);
    //         $this->createReceptionist($this->record, $receptionistUser);
    //     }
    // }

    private function createExpiryDates(): void
    {
        $expiryFields = [
            'expiry_date' => ['property_management_id', 'Property Management'],
            'sg_expiry_date' => ['sgoc_company_id', 'Sgoc'],
            'insurance_expiry_date' => ['insurance_company_id', 'Fire Insurance'],
            // 'maid_expiry_date' => ['maid_id', 'Residence'], // Uncomment if needed
        ];

        foreach ($expiryFields as $field => [$idField, $type]) {
            if (! empty($this->data[$field])) {
                $subscription_expiry = new SubscriptionExpire;
                $subscription_expiry->type = $type;
                $subscription_expiry->company_id = $this->record->{$idField} ?? null;
                $subscription_expiry->residence_id = $this->record->id;
                $subscription_expiry->expiry_date = $this->data[$field];
                $subscription_expiry->save();
            }
        }
    }

    private function createResidenceFeature($is_active, $feature, $residence_id)
    {
        $featureId = ResidenceFeatureSupport::formKeyToFeatureMap()[$feature] ?? null;

        if ($featureId === null) {
            return;
        }

        $this->storeResidenceFeature((int) $residence_id, (int) $featureId, (int) $is_active);

        if (in_array((int) $featureId, ResidenceFeatureSupport::accountManagedFeatureIds(), true)) {
            $this->storeResidenceAccount((int) $featureId, (int) $is_active);
        }
    }

    private function storeResidenceFeature(int $residence_id, int $feature_id, int $is_active): void
    {
        ResidenceFeature::create([
            'residence_id' => $residence_id,
            'feature_id' => $feature_id,
            'is_active' => $is_active,
        ]);
    }

    private function storeResidenceAccount(int $type, int $is_active): void
    {
        $featureRoleMap = ResidenceFeatureSupport::accountRoleMap();

        if ($is_active && isset($featureRoleMap[$type])) {
            [$role1, $role2] = $featureRoleMap[$type];
            AccountHelper::manageAccountCreation($this->record, $role1, $role2);
        }
    }
}
