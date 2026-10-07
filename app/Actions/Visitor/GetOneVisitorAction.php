<?php

namespace App\Actions\Visitor;

use App\Models\VisitorLog;

class GetOneVisitorAction
{
    public function execute(int $id)
    {
        return VisitorLog::with('visitorParking')->findOrFail($id);
    }
}
