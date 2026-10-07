<?php

namespace App\Actions\Company;

use App\Models\Company;

class GetCompanyAction
{
    public function execute($request)
    {
        $companies = Company::query();

        if (isset($request->type)) {
            $companies->where('type', $request->type);
        }

        if (isset($request->name)) {
            $companies->where('name', 'LIKE', '%'.$request->name.'%');
        }

        if (isset($request->contact_email)) {
            $companies->where('contact_email', 'LIKE', '%'.$request->contact_email.'%');
        }

        if (isset($request->has_pagination) && ($request->has_pagination == false)) {
            return $companies->get();
        }

        return $companies->paginate(25);
    }
}
