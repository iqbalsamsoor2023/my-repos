<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\BillPayeeBankDetail;
use App\Models\BillPayeeSetting;
use Exception;
use Illuminate\Support\Facades\DB;

class BillPayeeBankDetailsData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            $mmb1Data = DB::connection('mmb1')
                ->table('bill_reminder_bank_details')
                ->select('*', 'bill_reminder_bank_details.id as id', 'bill_reminder_bank_details.bill_reminder_setting_id as bill_reminder_setting_id', 'bill_reminder_bank_details.created_at as created_at', 'bill_reminder_bank_details.updated_at as updated_at')
                ->leftJoin('bill_reminder_settings', 'bill_reminder_settings.id', 'bill_reminder_bank_details.bill_reminder_setting_id')
                ->where('bill_reminder_settings.residence_id', $residence_id)
                ->orderBy('bill_reminder_bank_details.id', 'asc')
                ->chunk(1000, function ($datas) use ($residence_id) {
                    foreach ($datas as $key => $value) {
                        $bill_payee_setting = BillPayeeSetting::where('payee_name', $value->name)
                            ->where('residence_id', $residence_id)
                            ->first();

                        $bill_payee_bank_detail = BillPayeeBankDetail::create([
                            'bill_payee_setting_id' => $bill_payee_setting->id,
                            'bank_id' => $value->bank_id,
                            'payee_account_name' => $value->bank_account_name,
                            'payee_account_name_th' => $value->bank_account_name_th,
                            'payee_account_number' => $value->account_number,
                            'created_at' => $value->created_at,
                            'updated_at' => $value->updated_at,
                        ]);
                    }
                });

            DB::commit();

            return true;
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }
}
