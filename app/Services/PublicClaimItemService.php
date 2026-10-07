<?php

namespace App\Services;

use App\Actions\PublicClaimItem\GetPublicClaimItemAction;
use Illuminate\Http\Request;

class PublicClaimItemService
{
    public function index(Request $request)
    {
        $getPublicClaimItemAction = new GetPublicClaimItemAction;
        $publicClaimItems = $getPublicClaimItemAction->execute($request);

        return $publicClaimItems;
    }
}
