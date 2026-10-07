<?php

namespace App\Services;

use App\Models\VisitorRemark;

class VisitorRemarkService
{
    public function index($request)
    {
        return VisitorRemark::where('residence_id', $request->residence_id)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();
    }
}
