<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\BillPayeeSetting;
use Exception;
use Illuminate\Support\Facades\DB;

class BillPayeeSettingsData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            $mmb1_bill_reminder_setting = DB::connection('mmb1')
                ->table('bill_reminder_settings')
                ->where('residence_id', $residence_id)
                ->orderBy('id', 'asc')
                ->first();

            if ($mmb1_bill_reminder_setting) {
                $bill_payee_setting = BillPayeeSetting::create([
                    'id' => $mmb1_bill_reminder_setting->id,
                    'residence_id' => $mmb1_bill_reminder_setting->residence_id,
                    'payee_name' => $mmb1_bill_reminder_setting->name,
                    'payee_name_th' => $mmb1_bill_reminder_setting->name_th,
                    'payee_email' => $mmb1_bill_reminder_setting->email,
                    'payee_phone_no' => $mmb1_bill_reminder_setting->phone_no,
                    'payee_address' => $mmb1_bill_reminder_setting->address,
                    'payee_address_th' => $mmb1_bill_reminder_setting->address_th,
                    'remark' => $mmb1_bill_reminder_setting->remark,
                    'remark_th' => $mmb1_bill_reminder_setting->remark_th,
                    'created_at' => $mmb1_bill_reminder_setting->created_at,
                    'updated_at' => $mmb1_bill_reminder_setting->updated_at,
                ]);
            }
            DB::commit();

            return true;
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }
}
