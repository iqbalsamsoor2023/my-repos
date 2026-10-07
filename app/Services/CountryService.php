<?php

namespace App\Services;

use App\Actions\Country\GetCountryAction;
use App\Actions\Country\GetOneCountryAction;
use Illuminate\Http\Request;

class CountryService
{
    public function index(Request $request)
    {
        $countryAction = new GetCountryAction;
        $country = $countryAction->execute($request);

        return $country;
    }

    public function show(int $id)
    {
        $countryAction = new GetOneCountryAction;
        $country = $countryAction->execute($id);

        return $country;
    }
}
