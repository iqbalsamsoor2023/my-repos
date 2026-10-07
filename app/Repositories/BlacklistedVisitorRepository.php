<?php

namespace App\Repositories;

use App\Actions\BlacklistedVisitor\GetBlacklistedVisitorAction;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class BlacklistedVisitorRepository
{
    public function index(Request $request): LengthAwarePaginator
    {
        $blacklistedVisitorAction = new GetBlacklistedVisitorAction;

        return $blacklistedVisitorAction->execute($request);
    }
}
