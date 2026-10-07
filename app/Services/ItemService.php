<?php

namespace App\Services;

use App\Actions\Item\GetItemAction;
use Illuminate\Http\Request;

class ItemService
{
    public function index(Request $request)
    {
        $getItemAction = new GetItemAction;
        $item = $getItemAction->execute($request);

        return $item;
    }
}
