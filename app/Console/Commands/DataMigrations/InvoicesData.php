<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\BillPayeeBankDetail;
use App\Models\BillPayeeSetting;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Payer;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\Unit;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoicesData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            Item::disableModelHistory();
            Invoice::disableModelHistory();
            $mmb1Data = DB::connection('mmb1')
                ->table('bill_reminders')
                ->select('*', 'bill_reminders.id as id', 'bill_reminders.created_at as created_at', 'bill_reminders.updated_at as updated_at')
                ->leftJoin('residence_units', 'residence_units.id', 'bill_reminders.residence_unit_id')
                ->where('residence_units.residence_id', $residence_id)
                ->orderBy('bill_reminders.id', 'asc')
                ->chunk(1000, function ($datas) {
                    foreach ($datas as $key => $value) {
                        $bill_payee_setting_id = BillPayeeSetting::withTrashed()->where('residence_id', $value->residence_id)->value('id');

                        $unit = Unit::withTrashed()->where('home_id', $value->home_id)->where('unit_number', $value->unit)->first();

                        $invoice = new Invoice;
                        $invoice->invoice_no = $value->invoice_no;
                        $invoice->bill_payee_setting_id = $bill_payee_setting_id;
                        $invoice->payer_unit_id = $unit->id;
                        $invoice->bill_no = $value->bill_reminder_no;
                        $invoice->bill_date = $value->bill_date;
                        $invoice->due_date = $value->due_date;
                        $invoice->status = $value->status;
                        $invoice->remark = $value->remark;
                        $invoice->total_amount = $value->amount;
                        $invoice->amount_due = $value->outstanding_amount;
                        $invoice->created_at = $value->created_at;
                        $invoice->updated_at = $value->updated_at;
                        $invoice->save(['prevent_services' => true]);

                        $residence_users = null;
                        if ($value->bill_pay_by == 'All') {
                            $residence_users = DB::connection('mmb1')
                                ->table('residence_users')
                                ->where('residence_unit_id', $value->residence_unit_id)
                                ->get();
                        } elseif ($value->bill_pay_by == 'Main Owner') {
                            $residence_users = DB::connection('mmb1')
                                ->table('residence_users')
                                ->where('relationship', 0) // main owner
                                ->where('residence_unit_id', $value->residence_unit_id)
                                ->get();
                        } elseif ($value->bill_pay_by == 'Main Tenant') {
                            $residence_users = DB::connection('mmb1')
                                ->table('residence_users')
                                ->where('relationship', 11) // main tenant
                                ->where('residence_unit_id', $value->residence_unit_id)
                                ->get();
                        }

                        if ($residence_users) {
                            foreach ($residence_users as $residence_user) {
                                $mmb1_users = DB::connection('mmb1')
                                    ->table('users')
                                    ->where('id', $residence_user->user_id)
                                    ->first();
                                $payer = User::withTrashed()->where('email', $mmb1_users->email)->first();
                                if ($payer) {
                                    $payer = Payer::create([
                                        'invoice_id' => $invoice->id,
                                        'payer_id' => $payer->id,
                                    ]);
                                } else {
                                    Log::info('mystery payer :'.$mmb1_users->email);
                                }
                            }
                        }

                        // bill reminder drilldowns
                        $mmb1_bill_reminders = DB::connection('mmb1')
                            ->table('bill_reminder_drilldowns')
                            ->where('bill_reminder_id', $value->id)
                            ->get();

                        foreach ($mmb1_bill_reminders as $mmb1_bill_reminder) {
                            Item::create([
                                'invoice_id' => $invoice->id,
                                'name' => $mmb1_bill_reminder->expenses_type,
                                'price' => $mmb1_bill_reminder->amount,
                                'status' => $mmb1_bill_reminder->status,
                                'created_at' => $mmb1_bill_reminder->created_at,
                                'updated_at' => $mmb1_bill_reminder->updated_at,
                            ]);
                        }

                        // bill reminder slips
                        $mmb1_bill_reminder = null;
                        $mmb1_bill_reminders = DB::connection('mmb1')
                            ->table('bill_reminder_slips')
                            ->where('bill_reminder_id', $value->id)
                            ->get();

                        foreach ($mmb1_bill_reminders as $mmb1_bill_reminder) {
                            if ($mmb1_bill_reminder->payment_method == 'Bank In') {
                                $bill_payee_bank_detail_id = BillPayeeBankDetail::where('payee_account_name', $mmb1_bill_reminder->bank_account_name)->orWhere('payee_account_name_th', $mmb1_bill_reminder->bank_account_name)->value('id');
                                $payment_id = Payment::where('payment_mode', 'Cash Deposit')->value('id');
                            } elseif ($mmb1_bill_reminder->payment_method == 'Cash') {
                                $payment_id = Payment::where('payment_mode', 'Cash')->value('id');
                            }

                            $user_name = null;
                            if ($mmb1_bill_reminder->pay_by) {
                                $user_name = DB::connection('mmb1')
                                    ->table('users')
                                    ->where('id', $mmb1_bill_reminder->pay_by)
                                    ->value('name');
                            }

                            if ($mmb1_bill_reminder->reviewed_by) {
                                $mmb1_reviewed_by = DB::connection('mmb1')
                                    ->table('users')
                                    ->where('id', $mmb1_bill_reminder->reviewed_by)
                                    ->first();
                                $mmb2_reviewed_by = User::withTrashed()->where('email', $mmb1_reviewed_by->email)->first();
                            }

                            Transaction::create([
                                'ref_no' => $mmb1_bill_reminder->receipt_no,
                                'invoice_id' => $invoice->id, // tbc
                                'bill_payee_bank_detail_id' => $bill_payee_bank_detail_id ?? null,
                                'payment_id' => $payment_id,
                                'paid_amount' => $mmb1_bill_reminder->pay_amount,
                                'transaction_datetime' => $mmb1_bill_reminder->payment_date,
                                'status' => $mmb1_bill_reminder->status,
                                'payer_name' => $mmb1_bill_reminder->payer_name ?? $user_name,
                                'reviewed_by' => $mmb2_reviewed_by->id ?? null,
                                'remark' => $mmb1_bill_reminder->remark,
                                'created_at' => $mmb1_bill_reminder->created_at,
                                'updated_at' => $mmb1_bill_reminder->updated_at,
                            ]);
                        }
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
