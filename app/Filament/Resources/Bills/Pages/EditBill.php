<?php

namespace App\Filament\Resources\Bills\Pages;

use App\Enums\Bill\BillStatus;
use App\Enums\Bill\PaymentMode;
use App\Enums\Bill\TransactionStatus;
use App\Filament\Resources\Bills\BillResource;
use App\Helpers\AutomationTransactionReceiptGenerator;
use App\Jobs\SendEmailBillInvoiceUpdate;
use App\Jobs\SendEmailBillReceipt;
use App\Models\Payer;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\BillInvoiceCancelled;
use App\Notifications\BillInvoiceUpdated;
use App\Notifications\BillSlipAccepted;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Notification;

class EditBill extends EditRecord
{
    protected static string $resource = BillResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_bill');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->status != BillStatus::UNPAID->value && $this->record->status != BillStatus::CANCEL->value) {
            $user = User::where('name', 'like', '%' . $this->record->transactions[0]->payer_name . '%')->first();

            if ($user) {
                $data['payer_name'] = $user->id;
            } else {
                $data['payer_name'] = 'Other';
                $data['payer_name_other'] = $this->record->transactions[0]->payer_name;
            }
        }

        $data['residence_id'] = $this->record->unit->residence_id;
        $data['unit_number'] = $this->record->unit->unit_number;

        // `payment_id` is not an invoice column, so the form has nothing to fill
        // it with and its default is skipped on edit. Seed it here, otherwise the
        // select shows Cash while its state is still empty.
        $data['payment_id'] = PaymentMode::CASH->value;

        return $data;
    }

    protected function afterSave(): void
    {
        $payers = Payer::with('user')->where('invoice_id', $this->record->id)->get();

        if (in_array($this->record->status, [BillStatus::UNPAID->value, BillStatus::CANCEL->value])) {
            $total = $this->record->items
                ->where('status', '!=', BillStatus::CANCEL->value)
                ->sum('price');

            $this->record->update([
                'total_amount' => $total,
                'amount_due' => ($this->record->status === BillStatus::UNPAID->value) ? $total : 0.00,
            ]);

            if ($this->record->status === BillStatus::UNPAID->value) {
                foreach ($payers as $payer) {
                    if ($payer->user) {
                        Notification::send($payer->user, new BillInvoiceUpdated($this->record));
                    }
                }
            } elseif ($this->record->status === BillStatus::CANCEL->value) {
                foreach ($this->record->items as $item) {
                    $item->update(['status' => BillStatus::CANCEL->value]);
                }

                foreach ($payers as $payer) {
                    if ($payer->user) {
                        Notification::send($payer->user, new BillInvoiceCancelled($this->record));
                    }
                }
            }

            // Send email only if there are valid emails
            $payersWithEmail = $payers->filter(fn($payer) => $payer->user && $payer->user->email);
            if ($payersWithEmail->isNotEmpty()) {
                $emails = $payersWithEmail->map(fn($payer) => $payer->user->email)->toArray();
                SendEmailBillInvoiceUpdate::dispatch($this->record->id, $emails);
            }
        } else {
            $this->record->update(['amount_due' => 0.00]);

            // Update items (only non-cancelled)
            $this->record->items
                ->where('status', '!=', BillStatus::CANCEL->value)
                ->each(fn ($item) => $item->update([
                    'status' => BillStatus::PAID->value
                ]));

            // Resolve payer name
            $payerModel = User::find($this->data['payer_name']);

            // Create transaction
            $transaction = Transaction::create([
                'invoice_id' => $this->record->id,
                'payment_id' => $this->data['payment_id'] ?? PaymentMode::CASH->value,
                'paid_amount' => $this->record->total_amount,
                'transaction_datetime' => $this->data['transaction_datetime'] ?? now(),
                'status' => TransactionStatus::ACCEPTED->value,
                'payer_name' => $payerModel->name ?? $this->data['payer_name_other'],
                'reviewed_by' => auth()->id(),
            ]);

            // Generate ref no
            $transaction->update([
                'ref_no' => AutomationTransactionReceiptGenerator::generate($transaction)
            ]);

            // Extract users once
            $users = $payers
                ->pluck('user')
                ->filter();

            // Send notifications (no need for email check if using FCM)
            Notification::send($users, new BillSlipAccepted($transaction));

            // Send emails (only users with email)
            $emails = $users
                ->pluck('email')
                ->filter()
                ->values()
                ->all();

            if (! empty($emails)) {
                SendEmailBillReceipt::dispatch($transaction->id, $emails);
            }

            // Move media if exists
            if ($mediaItem = $this->record->getFirstMedia()) {
                $mediaItem->move($transaction, 'default', 'cos');
            }
        }
    }
}