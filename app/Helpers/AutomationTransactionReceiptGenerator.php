<?php

namespace App\Helpers;

use App\Models\Transaction;
use Exception;

class AutomationTransactionReceiptGenerator
{
    public static function generate(): string
    {
        $receipt_number = 'RE'.date('Y').date('m');
        $count_bill_slip = Transaction::where('ref_no', 'LIKE', '%'.$receipt_number.'%')->count();

        $attempts = 0;
        do {
            $new_receipt_number = $count_bill_slip + 1;
            $latest_receipt_number = $receipt_number.str_pad($new_receipt_number, 4, '0', STR_PAD_LEFT);
            $checkSameReceiptNo = Transaction::where('ref_no', $latest_receipt_number)->count();

            if ($checkSameReceiptNo > 0) {
                $attempts++;
            }

            return $latest_receipt_number;
        } while ($attempts < 10);

        if ($attempts >= 10) {
            throw new Exception('Failed to generate a unique receipt number');
        }
    }
}
