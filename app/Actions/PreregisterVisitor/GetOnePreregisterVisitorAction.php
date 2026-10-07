<?php

namespace App\Actions\PreregisterVisitor;

use App\Models\PreregisterVisitor;
use Illuminate\Http\Request;

class GetOnePreregisterVisitorAction
{
    public function execute(Request $request)
    {
        $preregisterVisitor = PreregisterVisitor::with('visitor', 'unit');

        if (isset($request->visitor_code)) {
            $preregisterVisitor = $preregisterVisitor->where('visitor_code', $request->visitor_code);
        }

        return $preregisterVisitor->first();
    }
}
