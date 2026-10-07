<?php

namespace App\Actions\Country;

use App\Models\Country;

class GetOneCountryAction
{
    public function execute(int $id)
    {
        $country = Country::select('id', 'name', 'phone_prefix')->find($id);

        return $country;
    }
}
