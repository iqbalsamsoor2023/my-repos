<?php

namespace App\Filament\Resources\BillTransactions\Pages;

use Filament\Actions\DeleteAction;
use App\Enums\Bill\BillStatus;
use App\Enums\Bill\TransactionStatus;
use App\Filament\Resources\BillTransactions\BillTransactionResource;
use App\Helpers\AutomationTransactionReceiptGenerator;
use App\Jobs\SendEmailBillReceipt;
use App\Models\Payer;
use App\Notifications\BillSlipAccepted;
use App\Notifications\BillSlipRejected;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Notification;

class EditBillTransaction extends EditRecord
{
    protected static string $resource = BillTransactionResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_bill_reminder_slip');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['transaction_time'] = $data['transaction_datetime'];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['status'] == TransactionStatus::ACCEPTED->value) {
            $data['ref_no'] = AutomationTransactionReceiptGenerator::generate($this->record);
            $data['reviewed_by'] = auth()->user()->id;
        }

        $data['transaction_datetime'] = $data['transaction_datetime'].' '.$data['transaction_time'].':00';

        return $data;
    }

    protected function afterSave(): void
    {
        $invoice = $this->record->invoice;
        $payers = Payer::with('user')->where('invoice_id', $invoice->id)->get();

        if ($this->record->status == TransactionStatus::ACCEPTED->value) {
            $invoice->update(['status' => BillStatus::PAID->value]);

            foreach ($invoice->items->where('status', '!=', BillStatus::CANCEL->value) as $item) {
                $item->update(['status' => BillStatus::PAID->value]);
            }

            foreach ($payers as $payer) {
                if ($payer->user) {
                    Notification::send($payer->user, new BillSlipAccepted($this->record));
                }
            }

            // Email to payers with valid email
            $emails = $payers->filter(fn($payer) => $payer->user && $payer->user->email)
                            ->map(fn($payer) => $payer->user->email)
                            ->toArray();

            if (!empty($emails)) {
                SendEmailBillReceipt::dispatch($this->record->id, $emails);
            }

        } elseif ($this->record->status == TransactionStatus::REJECTED->value) {
            $invoice->update(['status' => BillStatus::UNPAID->value]);

            foreach ($invoice->items->where('status', '!=', BillStatus::CANCEL->value) as $item) {
                $item->update(['status' => BillStatus::UNPAID->value]);
            }

            foreach ($payers as $payer) {
                if ($payer->user) {
                    Notification::send($payer->user, new BillSlipRejected($this->record));
                }
            }
        }
    }
}

