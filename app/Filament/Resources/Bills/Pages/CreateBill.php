<?php

namespace App\Filament\Resources\Bills\Pages;

use App\Enums\Bill\BillStatus;
use App\Filament\Resources\Bills\BillResource;
use App\Jobs\SendEmailBillInvoice;
use App\Models\BillPayeeSetting;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Payer;
use App\Models\User;
use App\Notifications\BillInvoiceCreated;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;

class CreateBill extends CreateRecord
{
    protected static string $resource = BillResource::class;

    public function getTitle(): string
    {
        return __('menu.create_bill');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        Item::disableModelHistory();
        Invoice::disableModelHistory();

        $month = date('m', strtotime($data['bill_date']));
        $year = date('Y', strtotime($data['bill_date']));
        $bill = Invoice::whereMonth('bill_date', $month)->whereYear('bill_date', $year)->get();
        $invoiceNo = 'INV'.$year.$month.str_pad($bill->count() + 1, 4, '0', STR_PAD_LEFT);

        $billSetting = BillPayeeSetting::where('residence_id', $data['residence_id'])->first();

        $data['invoice_no'] = $invoiceNo;
        $data['bill_payee_setting_id'] = $billSetting->id;
        $data['status'] = BillStatus::UNPAID->value;

        $invoice = static::getModel()::create($data);

        if ($data['notification'] == 'Main Owner') {
            foreach ($invoice->unit->unitUsers as $unitUser) {
                if ($unitUser->is_main_owner == true) {
                    $this->createPayer($invoice, $unitUser);
                }
            }
        } elseif ($data['notification'] == 'Main Tenant') {
            foreach ($invoice->unit->unitUsers as $unitUser) {
                if ($unitUser->is_main_tenant == true) {
                    $this->createPayer($invoice, $unitUser);
                }
            }
        } else {
            foreach ($invoice->unit->unitUsers as $unitUser) {
                $this->createPayer($invoice, $unitUser);
            }
        }

        return $invoice;
    }

    protected function afterCreate(): void
    {
        // Calculate total
        $total = $this->record->items->sum('price');

        $this->record->update([
            'total_amount' => $total,
            'amount_due' => $total,
        ]);

        // If fully paid
        if ($this->record->amount_due == 0.00) {
            foreach ($this->record->items as $item) {
                $item->update([
                    'status' => BillStatus::PAID->value,
                ]);
            }

            $this->record->update([
                'status' => BillStatus::PAID->value,
            ]);
        }

        Item::enableModelHistory();
        Invoice::enableModelHistory();

        // Eager load users (avoid N+1)
        $payers = Payer::with('user')
            ->where('invoice_id', $this->record->id)
            ->get();

        // Extract valid users only
        $users = $payers->pluck('user')->filter();

        // Send notification safely
        if ($users->isNotEmpty()) {
            Notification::send($users, new BillInvoiceCreated($this->record));
        }

        SendEmailBillInvoice::dispatch($this->record->id);
    }

    private function createPayer($invoice, $unitUser)
    {
        if (!$unitUser->user_id) {
            return; // Skip creating a payer if no user_id
        }
    
        // verify user exists
        $userExists = User::where('id', $unitUser->user_id)->exists();
        if (!$userExists) {
            return;
        }

        Payer::create([
            'invoice_id' => $invoice->id,
            'payer_id' => $unitUser->user_id,
        ]);
    }
}
