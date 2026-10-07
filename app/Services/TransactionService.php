<?php

namespace App\Services;

use App\Actions\Notification\CountUnreadNotificationAction;
use App\Actions\Transaction\CreateTransactionAction;
use App\Actions\Transaction\GenerateReceiptFileDataAction;
use App\Actions\Transaction\GetOneTransactionAction;
use App\Actions\Transaction\GetTransactionAction;
use App\Actions\Transaction\UpdateInvoiceStatusAction;
use App\Enums\Bill\BillStatus;
use App\Enums\Bill\PaymentMode;
use App\Exceptions\GeneralException;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Filament\Resources\BillTransactions\BillTransactionResource;
use App\Models\User;
use App\Notifications\BillSlipPaid;
use App\Support\Notifications\DashboardNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Notification;

class TransactionService
{
    public function index(Request $request)
    {
        $getTransactionAction = new GetTransactionAction;
        $transaction = $getTransactionAction->execute($request);

        return $transaction;
    }

    public function show(Request $request, int $id)
    {
        $getOneTransactionAction = new GetOneTransactionAction;
        $transaction = $getOneTransactionAction->execute($request, $id);

        return $transaction;
    }

    public function uploadSlip(StoreTransactionRequest $request)
    {
        $invoice = Invoice::with('items')->where('status', '!=', BillStatus::CANCEL->value)->findOrFail($request->invoice_id);

        if ($invoice->status == BillStatus::CANCEL->value) {
            return response()->json([
                'message' => __('No need to pay for Cancelled invoice')
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
        
        if ($invoice->status == BillStatus::PENDING->value) {
            return response()->json([
                'message' => __('Your request is still pending, please wait for the verification')
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
        
        if ($invoice->status == BillStatus::PAID->value) {
            return response()->json([
                'message' => __('This bill has been paid')
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $createTransactionAction = new CreateTransactionAction;

        $request->merge(['payment_id' => PaymentMode::CASH_DEPOSIT->value]);
        $transaction = $createTransactionAction->execute($request);

        $updateInvoiceStatusAction = new UpdateInvoiceStatusAction($invoice);
        $invoice = $updateInvoiceStatusAction->execute($invoice);

        $this->sendNotificationToPm($invoice, $transaction);

        return $transaction;
    }

    public function downloadReceipt(int $id)
    {
        $generateReceiptFileDataAction = new GenerateReceiptFileDataAction;

        $transaction = Transaction::findOrFail($id);
        $data = $generateReceiptFileDataAction->execute($transaction);

        $pdf = PDF::loadView('bills.receipt-full', $data)->setPaper('A4');

        return $pdf->download('receipt_'.now().'.pdf');
    }

    public function sendNotificationToPm(Invoice $invoice, Transaction $transaction)
    {
        $count_notification_action = new CountUnreadNotificationAction; // for ios badge need server side count

        $pmId = $invoice->unit?->residence?->property_management_user_id;
        $pmUser = User::hasDevice()->find($pmId);

        if (!$pmUser) {
            return; // Skip notification if PM not found
        }

        Notification::send($pmUser, new BillSlipPaid($invoice, $transaction, $count_notification_action));

        // send notification to PM dashboard
        $params = [
            'residentName' => $transaction->payer_name,
            'unit' => $invoice->unit?->unit_number,
            'amount' => number_format($transaction->paid_amount, 2),
            'invoiceNo' => $invoice->invoice_no,
        ];

        DashboardNotification::make('notification.bill_slip_paid.title')
            ->body('notification.bill_slip_paid.body')
            ->params($params)
            ->icon(Heroicon::OutlinedBanknotes, 'success')
            ->viewAction(BillTransactionResource::getUrl('view', ['record' => $transaction->id]))
            ->sendToDatabase($pmUser);
    }
}
