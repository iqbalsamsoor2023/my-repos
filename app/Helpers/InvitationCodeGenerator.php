<?php

namespace App\Helpers;

use App\Models\Unit;

class InvitationCodeGenerator
{
    public static function generate(Unit $unit, $type = null): string
    {
        $residenceId = str_pad($unit->residence_id, 5, '0', STR_PAD_LEFT);
        switch ($type) {
            case 'owner':
                // Addition of digit 0 because ensuring the header id consist 5 digits.
                $header = 1 .$residenceId;
                $checkingColumn = 'invitation_code_owner';
                break;

            case 'tenant':
                // Addition of digit 0 because ensuring the header id consist 5 digits.
                $header = 2 .$residenceId;
                $checkingColumn = 'invitation_code_tenant';
                break;

            default:
                return false;
                break;
        }

        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $newInvitationCode = $header.$random;

        // Check for invitation code uniqueness
        $hasSameInvitationCode = Unit::where($checkingColumn, $newInvitationCode)->count();
        if ($hasSameInvitationCode > 0) {
            $newInvitationCode = static::generate($unit, $type);
        }

        return $newInvitationCode;
    }
}
