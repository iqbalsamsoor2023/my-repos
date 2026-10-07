<?php

namespace App\Actions\Company;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;

class GetOneCompanyAction
{
    public function execute($id): ?Model
    {
        $company = Company::find($id);

        return $company;
    }
}
