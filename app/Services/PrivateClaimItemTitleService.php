<?php

namespace App\Services;

use App\Models\PrivateClaimItemTitle;
use Illuminate\Http\Request;

class PrivateClaimItemTitleService
{
    public function index(Request $request)
    {
        $privateclaimcategory = PrivateClaimItemTitle::where('private_claim_item_id', $request->private_claim_item_id)->orderBy('id')->paginate(10);

        return $privateclaimcategory;
    }
}