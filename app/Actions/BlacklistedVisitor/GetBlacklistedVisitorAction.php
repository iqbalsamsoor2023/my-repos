<?php

namespace App\Actions\BlacklistedVisitor;

use App\Models\BlacklistedVisitor;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class GetBlacklistedVisitorAction
{
    public function execute(Request $request): LengthAwarePaginator
    {
        $blacklistedVisitor = BlacklistedVisitor::with('visitor');

        if (isset($request->residence_id)) {
            $blacklistedVisitor = $blacklistedVisitor->where('residence_id', $request->residence_id);
        }

        return $blacklistedVisitor->orderBy('id')->paginate(25);
    }
}
