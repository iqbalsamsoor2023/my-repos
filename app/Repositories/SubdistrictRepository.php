<?php

namespace App\Repositories;

use App\Actions\Subdistrict\GetSubdistrictAction;
use Illuminate\Http\Request;

class SubdistrictRepository
{
    public function index(Request $request)
    {
        $subdistrictAction = new GetSubdistrictAction;
        $subdistricts = $subdistrictAction->execute($request);

        return $subdistricts;
    }
}
