<?php

namespace App\Repositories;

use App\Actions\ClaimableItem\GetClaimableItemAction;
use App\Actions\ClaimableItem\GetOneClaimableItemAction;
use Illuminate\Http\Request;

class ClaimableItemRepository
{
    public function index(Request $request)
    {
        $getAmenityAction = new GetClaimableItemAction;
        $amenity = $getAmenityAction->execute($request);

        return $amenity;
    }

    public function show(int $id)
    {
        $getOneClaimableItemAction = new GetOneClaimableItemAction;
        $claimableItem = $getOneClaimableItemAction->execute($id);

        return $claimableItem;
    }
}
