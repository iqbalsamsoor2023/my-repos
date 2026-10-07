<?php

namespace App\Filament\Resources\Residences\Pages;

use App\Actions\UserSgoc\CreateSecurityGuardUserAction;
use App\Actions\UserSgoc\UpdateSecurityGuardUserAction;
use App\Enums\Residence\Features;
use App\Enums\Residence\PropertyManagementType;
use App\Enums\User\RoleType;
use App\Filament\Resources\Residences\ResidenceResource;
use App\Helpers\AutomationAccountGenerator;
use App\Models\Company;
use App\Models\Erp\CdpCompany;
use App\Models\Erp\ThailandSubDistrict;
use App\Models\PrivateClaimItem;
use App\Models\Residence;
use App\Models\ResidenceAmenity;
use App\Models\ResidenceFeature;
use App\Models\ResidencePrivateClaimItem;
use App\Models\SgocUser;
use App\Models\SubscriptionExpire;
use App\Models\User;
use App\Support\ResidenceFeatureSupport;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditResidence extends EditRecord
{
    protected static string $resource = ResidenceResource::class;

    private const SUBSCRIPTION_TYPE_PM = 'Property Management';

    private const SUBSCRIPTION_TYPE_SGOC = 'Sgoc';

    private const SUBSCRIPTION_TYPE_INSURANCE = 'Fire Insurance';

    public function getTitle(): string
    {
        return __('menu.edit_residence');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $residence = $this->record instanceof Residence ? $this->record : null;
        $guardUserId = (int) ($data['sgoc_residence_guard_user_id'] ?? 0);
        $user = $guardUserId > 0 ? SgocUser::query()->find($guardUserId, ['*']) : null;

        $data['sgoc_residence_security_guard_user_id'] = $user->email ?? null;
        $data['property_management_user_id'] = $residence?->propertyManagementUser?->email ?? null;
        $data['receptionist_user_id'] = $residence?->receptionist?->email ?? null;

        $companyId = (int) ($residence?->company_id ?? 0);
        $company = $companyId > 0 ? Company::query()->find($companyId, ['*']) : null;
        // $technicianCompany = Company::whereId($this->record->technician_id)->first();
        // $maidCompany = Company::whereId($this->record->maid_id)->first();

        $pm_subscription = SubscriptionExpire::query()
            ->whereIn('type', [self::SUBSCRIPTION_TYPE_PM], 'and', false)
            ->whereIn('residence_id', [(int) ($residence?->id ?? 0)], 'and', false)
            ->first(['*']);
        $sg_subscription = SubscriptionExpire::query()
            ->whereIn('type', [self::SUBSCRIPTION_TYPE_SGOC], 'and', false)
            ->whereIn('residence_id', [(int) ($residence?->id ?? 0)], 'and', false)
            ->first(['*']);
        $insurance_subscription = SubscriptionExpire::query()
            ->whereIn('type', [self::SUBSCRIPTION_TYPE_INSURANCE], 'and', false)
            ->whereIn('residence_id', [(int) ($residence?->id ?? 0)], 'and', false)
            ->whereIn('company_id', [(int) ($residence?->insurance_company_id ?? 0)], 'and', false)
            ->first(['*']);

        if ($residence?->company_id !== null && $company) {
            $data['company_contact_number'] = $company->contact_number;
            $data['company_address'] = $company->address;

            if ($company->person_in_charges !== null) {
                $data['company']['person_in_charges']['name'] = array_column($company->person_in_charges, 'name');
                $data['company']['person_in_charges']['email'] = array_column($company->person_in_charges, 'email');
                $data['company']['person_in_charges']['contact'] = array_column($company->person_in_charges, 'contact');
            }
        }

        // if (is_null($this->record->technician_id) == false) {
        //     if (isset($technicianCompany->person_in_charges)) {
        //         $data['technician']['person_in_charges']['name'] = array_column($technicianCompany->person_in_charges, 'name');
        //         $data['technician']['person_in_charges']['email'] = array_column($technicianCompany->person_in_charges, 'email');
        //         $data['technician']['person_in_charges']['contact'] = array_column($technicianCompany->person_in_charges, 'contact');
        //     }
        // }

        // if (is_null($this->record->maid_id) == false) {
        //     if (isset($maidCompany->person_in_charges)) {
        //         $data['maid']['person_in_charges']['name'] = array_column($maidCompany->person_in_charges, 'name');
        //         $data['maid']['person_in_charges']['email'] = array_column($maidCompany->person_in_charges, 'email');
        //         $data['maid']['person_in_charges']['contact'] = array_column($maidCompany->person_in_charges, 'contact');
        //     }
        // }

        $data['expiry_date'] = isset($pm_subscription) ? $pm_subscription->expiry_date : '';
        $data['sg_expiry_date'] = $sg_subscription?->expiry_date ?? '';
        $data['insurance_expiry_date'] = isset($insurance_subscription) ? $insurance_subscription->expiry_date : '';

        $featureMappings = ResidenceFeatureSupport::featureIdToFormKeyMap();

        $data['residenceFeatures'] = [];

        foreach ($this->record->residenceFeatures as $residenceFeature) {
            if (isset($featureMappings[$residenceFeature->feature_id])) {
                $key = $featureMappings[$residenceFeature->feature_id];
                $data['residenceFeatures'][$key] = $residenceFeature->is_active;
            }
        }

        $thailand = ThailandSubDistrict::with('district.province')->find($data['subdistrict_id']);

        $data['subdistrict_id'] = $thailand !== null ? $thailand->id : null;
        $data['district_id'] = $thailand !== null ? $thailand->district->id : null;
        $data['province_id'] = $thailand !== null ? $thailand->district->province->id : null;

        $data['activeFeatures'] = [
            'property_management' => $this->getActiveFeatureStatus($this->record->id, Features::PROPERTY_MANAGEMENT->value),
            'security_management' => $this->getActiveFeatureStatus($this->record->id, Features::SECURITY_MANAGEMENT->value),
            'receptionist' => $this->getActiveFeatureStatus($this->record->id, Features::RECEPTION_MANAGEMENT->value),
        ];

        $this->record->loadMissing('facilityAndAmenities');

        $activeAmenities = $this->record->facilityAndAmenities
            ->filter(function ($amenity) {
                return $amenity->pivot->is_active && $amenity->pivot->deleted_at == null;
            })
            ->pluck('id')
            ->map(fn ($id) => is_numeric($id) ? 'amenity_'.$id : $id)
            ->toArray();

        foreach ($activeAmenities as $key) {
            $data[$key] = true;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $hiddenTypes = [
            PropertyManagementType::ABANDONED->value,
            PropertyManagementType::NO_INFO->value,
            PropertyManagementType::PERSONAL_PROPERTY_MANAGEMENT->value,
        ];

        if (in_array($data['property_management_type'] ?? null, $hiddenTypes)) {
            $data['property_management_id'] = null;
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $selectedIds = collect($this->data)
            ->filter(fn ($value, $key) => str_starts_with($key, 'amenity_') && $value)
            ->keys()
            ->map(fn ($key) => (int) str_replace('amenity_', '', $key))
            ->toArray();

        $currentPivotItems = ResidenceAmenity::withTrashed()
            ->where('residence_id', $record->id)
            ->get();

        // Find pivot items to soft-delete (unselected ones)
        $toDelete = $currentPivotItems->filter(fn ($item) => ! in_array($item->facility_and_amenity_id, $selectedIds));

        // Soft-delete unselected pivot entries
        foreach ($toDelete as $item) {
            if (! $item->trashed() && isset($item->id)) {
                ResidenceAmenity::destroy($item->id);
            }
        }

        // Process selectedIds to attach new or restore soft-deleted
        foreach ($selectedIds as $facilityAndAmenityId) {
            $existing = $currentPivotItems->firstWhere('facility_and_amenity_id', $facilityAndAmenityId);

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore(); // Restore soft-deleted pivot entry
                    $existing->update(['is_active' => true]);
                } else {
                    $existing->update(['is_active' => true]); // Pivot exists and active — update it
                }
            } else {
                // Attach new pivot entry
                $record->facilityAndAmenities()->attach($facilityAndAmenityId, [
                    'is_active' => true,
                ]);
            }
        }

        if (! empty($data['developer_id'])) {
            $developerUserId = CdpCompany::whereId((int) $data['developer_id'])->value('mmb_user_id');
            $data['developer_user_id'] = $developerUserId;
        }

        $record->update($data);

        return $record;
    }

    protected function afterSave(): void
    {
        $residence = $this->record instanceof Residence ? $this->record : null;

        if (! $residence) {
            return;
        }

        $residenceGuardUser = $residence->residenceGuardUser;
        $newCompanyId = $residence->sgoc_company_id;

        if ($residenceGuardUser && $newCompanyId && $residenceGuardUser->company_id !== $newCompanyId) {
            $residenceGuardUser->update([
                'company_id' => $newCompanyId,
            ]);
        }

        if ($residence->sgoc_residence_guard_user_id !== null) {
            $securityGuardUserAction = new UpdateSecurityGuardUserAction;
            $securityGuardUserAction->execute($residence, (int) $residence->sgoc_residence_guard_user_id);
        }

        if (! empty($this->data['expiry_date'])) {
            $this->updateSubscriptionExpireDate($residence, $this->data['expiry_date'], $this->data['property_management_id'], self::SUBSCRIPTION_TYPE_PM);
        }

        if (! empty($this->data['sg_expiry_date'])) {
            $this->updateSubscriptionExpireDate($residence, $this->data['sg_expiry_date'], $this->data['sgoc_company_id'], self::SUBSCRIPTION_TYPE_SGOC);
        }

        if (! empty($this->data['insurance_expiry_date'])) {
            $this->updateSubscriptionExpireDate($residence, $this->data['insurance_expiry_date'], $this->data['insurance_company_id'], self::SUBSCRIPTION_TYPE_INSURANCE);
        }

        foreach (($this->data['residenceFeatures'] ?? []) as $key => $residenceFeature) {
            $this->createOrUpdateResidenceFeature($residenceFeature, $key, $residence->id);
        }

        $record = $this->record;
        $state = $this->form->getState();
    
        // Wizard step state (you MUST add ->statePath('private_claim_items_state'))
        $privateState = $state['private_claim_items_state'] ?? [];
    
        foreach (PrivateClaimItem::all() as $item) {
    
            $key = 'residence_private_claim_item_' . $item->id;
            $isActive = !empty($privateState[$key]) ? 1 : 0;
    
            // Check if mapping exists
            $existing = ResidencePrivateClaimItem::where('residence_id', $record->id)
                ->where('private_claim_item_id', $item->id)
                ->first();
    
            if ($existing) {
                // Update existing
                $existing->update([
                    'is_active' => $isActive,
                ]);
            } else {
                // Create new
                ResidencePrivateClaimItem::create([
                    'residence_id'          => $record->id,
                    'private_claim_item_id' => $item->id,
                    'is_active'             => $isActive,
                ]);
            }
        }
    }

    private function updateSubscriptionExpireDate(Residence $model, string $expiry_date, ?int $new_company_id, string $type): void
    {
        if ($model->sgoc_residence_guard_user_id === null) {
            // create sgoc residence guard account
            $securityGuardUserAction = new CreateSecurityGuardUserAction;
            $securityGuard = $securityGuardUserAction->execute($model);
            $model->sgoc_residence_guard_user_id = $securityGuard['data']['id'];
            $model->save();

            $securityGuardUserAction = new UpdateSecurityGuardUserAction;
            $securityGuardUserAction->execute($model, (int) $model->sgoc_residence_guard_user_id);
        }

        $subscription_expiry = SubscriptionExpire::query()
            ->whereIn('type', [$type], 'and', false)
            ->whereIn('residence_id', [$model->id], 'and', false)
            ->first(['*']);

        if (! $subscription_expiry) {
            SubscriptionExpire::create([
                'type' => $type,
                'company_id' => $new_company_id,
                'residence_id' => $model->id,
                'expiry_date' => $expiry_date,
            ]);
        } else {
            $subscription_expiry->update([
                'company_id' => $new_company_id,
                'expiry_date' => $expiry_date,
            ]);
        }
    }

    private function createOrUpdateResidenceFeature(int $is_active, $feature, int $residence_id)
    {
        $featureMap = ResidenceFeatureSupport::formKeyToFeatureMap();
        $featuresWithAccounts = ResidenceFeatureSupport::accountManagedFeatureIds();

        if (! isset($featureMap[$feature])) {
            return;
        }

        $type = $featureMap[$feature];
        $this->checkResidenceFeature($residence_id, $type, $is_active);

        if (in_array($type, $featuresWithAccounts, true)) {
            $this->checkResidenceAccount($type, $is_active);
        }

        // Special case for security management
        if ($type === Features::SECURITY_MANAGEMENT->value && $is_active === 1) {
            $residence = $this->record instanceof Residence ? $this->record : null;

            if ($residence && $residence->sgoc_residence_guard_user_id !== null) {
                $securityGuardUserAction = new UpdateSecurityGuardUserAction;
                $securityGuardUserAction->execute($residence, (int) $residence->sgoc_residence_guard_user_id);
            }
        }
    }

    private function checkResidenceFeature(int $residence_id, int $feature_id, int $is_active)
    {
        ResidenceFeature::updateOrCreate(
            [
                'residence_id' => $residence_id,
                'feature_id' => $feature_id,
            ],
            [
                'is_active' => $is_active,
            ]
        );
    }

    private function checkResidenceAccount(int $type, int $is_active)
    {
        // Handle Security Management separately as it uses SgocUser
        if ($type == Features::SECURITY_MANAGEMENT->value) {
            $this->handleSecurityGuardAccount($is_active);

            return;
        }

        // Configuration for all other account types
        $accountConfig = [
            Features::SALES_MANAGEMENT->value => [
                'user_field' => 'sales_management_user_id',
                'role_type' => RoleType::SM,
                'role_name' => RoleType::SALES_MANAGEMENT,
            ],
            Features::RECEPTION_MANAGEMENT->value => [
                'user_field' => 'receptionist_user_id',
                'role_type' => RoleType::RC,
                'role_name' => RoleType::RECEPTIONIST,
            ],
            Features::RESALE_AND_TENANCY->value => [
                'user_field' => 'rtm_user_id',
                'role_type' => RoleType::RTM,
                'role_name' => RoleType::RESALES_AND_TENANCY_MANAGEMENT,
            ],
            Features::FACILITIES_MANAGEMENT->value => [
                'user_field' => 'technician_user_id',
                'role_type' => RoleType::FM,
                'role_name' => RoleType::FACILITIES_MANAGEMENT,
            ],
            Features::PROPERTY_MANAGEMENT->value => [
                'user_field' => 'property_management_user_id',
                'role_type' => RoleType::PM,
                'role_name' => RoleType::PROPERTY_MANAGEMENT,
            ],
            Features::ACCOUNTING_MANAGEMENT->value => [
                'user_field' => 'accountant_user_id',
                'role_type' => RoleType::AC,
                'role_name' => RoleType::ACCOUNTANT,
            ],
        ];

        if (isset($accountConfig[$type])) {
            $config = $accountConfig[$type];
            $userId = $this->record->{$config['user_field']};
            $this->handleUserAccount($userId, $is_active, $config['role_type']->value, $config['role_name']->value);
        }
    }

    private function handleUserAccount(?int $userId, int $is_active, string $roleType, string $roleName): void
    {
        $active = (int) $is_active;

        if ($userId !== null && $active == 0) {
            $this->deleteUser($userId);
        } elseif ($userId !== null && $active == 1) {
            $this->restoreUser($userId);
            // Re-attach role when activating existing user
            $user = User::query()->find((int) $userId, ['*']);

            if ($user instanceof User) {
                AutomationAccountGenerator::attachRole($user, $roleName);
            }
        } elseif ($userId == null && $active == 1) {
            $this->manageAccountCreation($roleType, $roleName);
        }
    }

    private function handleSecurityGuardAccount(int $is_active): void
    {
        $scUserId = $this->record->sgoc_residence_guard_user_id;

        $active = (int) $is_active;

        if ($scUserId !== null && $active == 0) {
            SgocUser::destroy($scUserId);
        } elseif ($scUserId !== null && $active == 1) {
            $existingAccount = SgocUser::whereId($scUserId)->whereNotNull('deleted_at', 'and')->exists();

            if ($existingAccount) {
                SgocUser::whereId($scUserId)->update(['deleted_at' => null]);
            }
        } elseif ($scUserId == null && $active == 1) {
            $this->manageAccountCreation(RoleType::SC->value, RoleType::SECURITY_GUARD->value);
        }
    }

    private function deleteUser(int $id): void
    {
        User::whereId($id)->delete();
    }

    private function restoreUser(int $id): void
    {
        User::withTrashed()->whereId($id)->restore();
    }

    private function manageAccountCreation(string $roleType, string $roleName): void
    {
        $residence = $this->record instanceof Residence ? $this->record : null;

        if (! $residence) {
            return;
        }

        if ($roleName == RoleType::SECURITY_GUARD->value) {
            $securityGuardUserAction = new CreateSecurityGuardUserAction;
            $securityGuard = $securityGuardUserAction->execute($residence);
            $this->manageResidenceGuard($residence, $securityGuard);
        } else {
            $generateAccount = AutomationAccountGenerator::generate($residence, $roleType);
            $existingAccount = User::withTrashed()->where('email', $generateAccount['email'])->first();

            if (! $existingAccount) {
                $accountCreation = User::create(AutomationAccountGenerator::manageAccount($generateAccount));
                AutomationAccountGenerator::attachRole($accountCreation, $roleName);
            } else {
                $this->restoreUser($existingAccount->id);
                // Refresh the model to get the restored state
                $accountCreation = User::query()->find((int) $existingAccount->id, ['*']);
                // Re-attach role in case it was removed
                if ($accountCreation instanceof User) {
                    AutomationAccountGenerator::attachRole($accountCreation, $roleName);
                }
            }

            if ($accountCreation instanceof User) {
                $this->manageAccount($residence, $accountCreation, $roleName);
            }
        }
    }

    private function manageAccount(Residence $residence, User $user, string $role): void
    {
        $fieldMap = [
            RoleType::SALES_MANAGEMENT->value => 'sales_management_user_id',
            RoleType::RECEPTIONIST->value => 'receptionist_user_id',
            // RoleType::FACILITIES_MANAGEMENT->value => 'technician_user_id',
            RoleType::PROPERTY_MANAGEMENT->value => 'property_management_user_id',
            RoleType::RESALES_AND_TENANCY_MANAGEMENT->value => 'rtm_user_id',
            RoleType::ACCOUNTANT->value => 'accountant_user_id',
        ];

        if (isset($fieldMap[$role])) {
            $residence->{$fieldMap[$role]} = $user->id;
            $residence->save();
        }
    }

    private function manageResidenceGuard(Residence $residence, $sgoc_user): void
    {
        $residence->sgoc_residence_guard_user_id = $sgoc_user['data']['id'];
        $residence->save();
    }

    private function getActiveFeatureStatus($residenceId, $featureId)
    {
        return ResidenceFeature::query()
            ->whereIn('residence_id', [(int) $residenceId], 'and', false)
            ->whereIn('feature_id', [(int) $featureId], 'and', false)
            ->whereIn('is_active', [1], 'and', false)
            ->exists();
    }
}
