<?php

namespace App\Repositories;

use App\Actions\Company\GetCompanyAction;
use App\Actions\Company\GetOneCompanyAction;
use Illuminate\Database\Eloquent\Model;

class CompanyRepository
{
    public function index($request)
    {
        $getCompanyAction = new GetCompanyAction;
        $companies = $getCompanyAction->execute($request);

        return $companies;
    }

    public function show($id): ?Model
    {
        $getOneCompanyAction = new GetOneCompanyAction;
        $company = $getOneCompanyAction->execute($id);

        return $company;
    }
}
