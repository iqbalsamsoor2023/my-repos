<?php

namespace App\Repositories;

use App\Actions\BillPayeeSetting\GetResidenceBankAccountAction;
use Illuminate\Http\Request;

class BillPayeeSettingRepository
{
    public function getResidenceBankAccountList(Request $request)
    {
        $getBankAccountAction = new GetResidenceBankAccountAction;
        $residenceBankAccounts = $getBankAccountAction->execute($request);

        return $residenceBankAccounts;
    }
}
