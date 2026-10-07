<?php

namespace App\Repositories;

use App\Actions\VisitorPurpose\GeVisitorPurposeAction;
use Illuminate\Http\Request;

class VisitorPurposeRepository
{
    public function index(Request $request)
    {
        $geVisitorPurposeAction = new GeVisitorPurposeAction;
        $visitorPurposes = $geVisitorPurposeAction->execute($request);

        return $visitorPurposes;
    }
}
