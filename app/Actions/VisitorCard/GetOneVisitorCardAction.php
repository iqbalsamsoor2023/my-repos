<?php

namespace App\Actions\VisitorCard;

use App\Models\VisitorCard;

class GetOneVisitorCardAction
{
    public function execute(int $id)
    {
        return VisitorCard::findOrFail($id);
    }
}
