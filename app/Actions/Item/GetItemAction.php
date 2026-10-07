<?php

namespace App\Actions\Item;

use App\Models\Item;

class GetItemAction
{
    public function execute($request)
    {
        $item = Item::with('invoice', 'invoice.unit');

        if (isset($request->invoice_id)) {
            $item = $item->where('invoice_id', $request->invoice_id);
        }

        if (isset($request->status)) {
            $item = $item->where('status', $request->status);
        }

        return $item->get();
    }
}
