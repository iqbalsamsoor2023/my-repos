<?php

namespace App\Actions\VisitorCard;

use App\Models\VisitorCard;
use Illuminate\Http\Request;

class GetVisitorCardAction
{
    public function execute(Request $request)
    {
        $visitorCards = VisitorCard::query();

        if (isset($request->residence_id)) {
            $visitorCards = $visitorCards->where('residence_id', $request->residence_id);
        }

        if (isset($request->visitor_card_no)) {
            $visitorCards = $visitorCards->where('visitor_card_no', $request->visitor_card_no);
        }

        if (isset($request->has_pagination) && ($request->has_pagination == false)) {
            return $visitorCards->get();
        }

        return $visitorCards->orderBy('id', 'DESC')->paginate(25);
    }
}
