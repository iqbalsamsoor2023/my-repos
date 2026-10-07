<?php

namespace App\Helpers\Sgoc;

use App\Enums\User\RoleType;
use App\Models\Erp\DtaDigitalTool;
use App\Models\Residence;
use App\Models\Sgoc\User;
use Illuminate\Database\Eloquent\Model;

class GuardTalkAccountGenerator
{
    public static function execute(Model $residenceData): array
    {
        // Residence Guard Talk Device
        // 103005 03019 gt 01 @mymooban.co.th
        // Subdistrict Code: 103005
        // Residence ID (5-Digit): 03019
        // RoleType: gt
        // 2-Digit Total of subscribe device in each residence (following by sequence): 01
        // Prefix email: @mymooban.co.th
        $securityGuardStaffAbbreviation = RoleType::GT->value;
        $emailDomain = '@mymooban.co.th';

        $residence = Residence::with('propertyManagementUser')
            ->findOrFail($residenceData->id);

        // Extract subdistrict code and residence ID from PM email
        $pmEmail = $residence->propertyManagementUser->email ?? '';

        if (!preg_match('/^(\d{6})pm(\d{5})/i', $pmEmail, $matches)) {
            throw new \Exception("Invalid PM email format: $pmEmail");
        }

        $subdistrictCode = $matches[1];  // '103005'
        $residenceId = $matches[2]; // '03019'

        $usernamePrefix = "{$subdistrictCode}{$residenceId}{$securityGuardStaffAbbreviation}";

        /*
        |--------------------------------------------------------------------------
        | Reuse existing unused account
        |--------------------------------------------------------------------------
        */
        $unusedDevice = DtaDigitalTool::where('user_platform', 'sgoc')
            ->whereNull('user_id')
            ->whereHas('skuCenter.digitalToolRole.digitalToolRoleCategory', function ($query) {
                $query->where('name', 'Guard Talk');
            })
            ->whereHas('digitalToolAllocation', function ($q) use ($residence) {
                $q->where('residence_id', $residence->id);
            })
            ->first();

        if ($unusedDevice && $unusedDevice->user) {

            return [
                'name' => $unusedDevice->user->name,
                'email' => $unusedDevice->user->email,
                'password' => null,
                'role' => RoleType::GUARD_TALK->value,
                'company_id' => $residence->sgoc_company_id,
                'user_id' => $unusedDevice->user->id,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Generate next available account
        |--------------------------------------------------------------------------
        */
        $counter = 1;

        do {

            $paddedDeviceNumber = str_pad($counter, 2, '0', STR_PAD_LEFT);

            $generatedUsername =
                "{$usernamePrefix}{$paddedDeviceNumber}";

            $generatedEmail =
                $generatedUsername . $emailDomain;

            $exists = User::where('email', $generatedEmail)->exists();

            $counter++;

        } while ($exists);

        return [
            'name' => $generatedUsername,
            'email' => $generatedEmail,
            'password' => $generatedUsername,
            'role' => RoleType::GUARD_TALK->value,
            'company_id' => $residence->sgoc_company_id,
        ];
    }
}