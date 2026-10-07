<?php

namespace App\Repositories;

use App\Actions\VisitorCard\GetOneVisitorCardAction;
use App\Actions\VisitorCard\GetVisitorCardAction;
use Illuminate\Http\Request;

class VisitorCardRepository
{
    public function index(Request $request)
    {
        $getVisitorCardAction = new GetVisitorCardAction;
        $visitorCards = $getVisitorCardAction->execute($request);

        return $visitorCards;
    }

    public function show(int $id)
    {
        $getOneVisitorCardAction = new GetOneVisitorCardAction;
        $visitorCard = $getOneVisitorCardAction->execute($id);

        return $visitorCard;
    }
}
