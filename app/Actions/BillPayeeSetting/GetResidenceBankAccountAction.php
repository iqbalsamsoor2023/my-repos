<?php

namespace App\Actions\BillPayeeSetting;

use App\Models\BillPayeeSetting;

class GetResidenceBankAccountAction
{
    public function execute($request)
    {
        $billPayeeSetting = BillPayeeSetting::with(['accounts.bank'])
            ->where('residence_id', $request->residence_id)->first();

        return $billPayeeSetting ? $billPayeeSetting->accounts : [];
    }
}
