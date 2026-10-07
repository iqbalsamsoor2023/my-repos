<?php

namespace App\Actions\VisitorPurpose;

use App\Models\VisitorPurpose;
use Illuminate\Http\Request;

class GeVisitorPurposeAction
{
    public function execute(Request $request)
    {
        $visitorPurpose = VisitorPurpose::select('id', 'purpose');

        if (isset($request->residence_id)) {
            $visitorPurpose = $visitorPurpose->where('residence_id', $request->residence_id);
        }

        return $visitorPurpose->orderBy('id', 'DESC')->paginate(20);
    }
}
