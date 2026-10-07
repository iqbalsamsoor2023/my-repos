<?php

namespace App\Repositories;

use App\Actions\Calculation\GetCalculationAction;
use Illuminate\Http\Request;

class CalculationRepository
{
    public function index(Request $request)
    {
        $getCalculationAction = new GetCalculationAction;
        $calculation = $getCalculationAction->execute($request);

        return $calculation;
    }
}
