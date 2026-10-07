<?php

namespace App\Actions\Invoice;

use App\Enums\Bill\BillStatus;
use App\Models\Invoice;

class CalculateTotalUnpaidBillAction
{
    public function execute(int $user_id, int $unit_id)
    {
        return Invoice::with('payers')->whereHas('payers', function ($query) use ($user_id) {
            $query->where('payer_id', $user_id);
        })
            ->where('payer_unit_id', $unit_id)
            ->where('status', BillStatus::UNPAID)
            ->count();
    }
}
