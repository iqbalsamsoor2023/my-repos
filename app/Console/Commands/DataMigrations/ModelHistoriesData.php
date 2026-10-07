<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\Invoice;
use App\Models\Item;
use App\Models\ModelHistory;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class ModelHistoriesData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();

            $residence_managements = DB::connection('mmb1')
                ->table('residence_managements')
                ->where('residence_id', $residence_id)->first();

            $mmb1Data = DB::connection('mmb1')
                ->table('model_histories')
                ->where('user_id', $residence_managements->user_id)
                ->orderBy('id', 'asc')
                ->chunk(1000, function ($datas) {
                    foreach ($datas as $key => $value) {
                        // user_id
                        $mmb1_user = DB::connection('mmb1')
                            ->table('users')
                            ->where('id', $value->user_id)
                            ->first();
                        $user = User::withTrashed()->where('email', $mmb1_user->email)->first();

                        if (isset($old_values)) {
                            $old_values = json_decode($value->old_values);

                            if ($value->modelable_type == 'App\Models\BillReminder') {
                                $old_values = Invoice::where('invoice_no', $old_values->invoice_no)->where('bill_no', $old_values->bill_reminder_no)->where('created_at', $old_values->created_at)->first();
                                $old_values = json_encode($old_values);
                            } elseif ($value->modelable_type == 'App\Models\BillReminderDrilldown') {
                                // {"id":1565,"bill_reminder_id":1192,"home_id":"104102020241010105","expenses_type":"water","amount":"30.00","outstanding_amount":"30.00","status":"1","created_at":"2022-07-13 15:01:10","updated_at":"2022-07-13 15:01:10"}
                                $old_values = Item::with('invoices')
                                    ->whereHas('invoice.unit', function ($query) use ($old_values) {
                                        $query->where('home_id', $old_values->home_id);
                                    })
                                    ->where('name', $old_values->expenses_type)->where('price', $old_values->amount)->where('created_at', $old_values->created_at)
                                    ->first();
                                $old_values = json_encode($old_values);
                            }
                        }

                        ModelHistory::create([
                            'user_id' => $user->id,
                            'event' => $value->event,
                            'modelable_type' => $value->modelable_type,
                            'modelable_id' => $value->modelable_id,
                            'old_values' => isset($old_values) ? $old_values : null,
                            'new_values' => $value->new_values,
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
