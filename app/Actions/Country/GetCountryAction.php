<?php

namespace App\Actions\Country;

use App\Models\Country;
use Illuminate\Http\Request;

class GetCountryAction
{
    public function execute(Request $request)
    {
        $countries = Country::query();

        if ($request->has('search')) {
            $search = $request->search;
            $countries->where('name', 'like', "%{$search}%");
        }

        if (isset($request->name)) {
            $countries = $countries->where('name', 'like', "%{$request->name}%");
        }

        return $countries->select('id', 'name', 'phone_prefix')->paginate(25);
    }
}
