<?php

namespace App\Actions\Company;

use App\Exceptions\GeneralException;
use App\Models\Company;
use Illuminate\Http\JsonResponse;

class CreateCompanyAction
{
    public function execute($request)
    {
        $company = Company::create($request->only([
            'province_id',
            'user_id',
            'type',
            'name',
            'name_th',
            'contact_email',
            'contact_number',
            'address',
            'person_in_charges',
            'website_url',
        ]));

        if (! $company) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed creating company');
        }

        return $company;
    }
}
