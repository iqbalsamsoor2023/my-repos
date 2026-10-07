<?php

namespace App\Repositories;

use App\Actions\PreregisterVisitor\CreatePreregisterVisitorAction;
use App\Actions\PreregisterVisitor\UpdatePreregisterVisitorAction;
use App\Actions\Visitor\CreateVisitorAction;
use App\Actions\Visitor\GetPreregisterVisitorAction;
use App\Http\Requests\PreregisterVisitor\StorePreregisterVisitorRequest;
use App\Http\Requests\PreregisterVisitor\UpdatePreregisterVisitorRequest;
use App\Models\PreregisterVisitor;
use Illuminate\Http\Request;

class PreregisterVisitorRepository
{
    public function index(Request $request)
    {
        $getPreregisterVisitorAction = new GetPreregisterVisitorAction;
        $prebookVisitors = $getPreregisterVisitorAction->execute($request);

        return $prebookVisitors;
    }

    public function create(StorePreregisterVisitorRequest $request)
    {
        $visitorAction = new CreateVisitorAction;
        $preregisterVisitorAction = new CreatePreregisterVisitorAction;

        $visitor = $visitorAction->execute($request);
        $preregister_visitor = $preregisterVisitorAction->execute($request, $visitor);

        $visitor = collect($visitor);
        $preregister_visitor = collect($preregister_visitor);

        return $visitor->merge($preregister_visitor);
    }

    public function update(UpdatePreregisterVisitorRequest $request, int $id)
    {
        $preregister_visitor = PreregisterVisitor::findOrFail($id);
        $preregisterVisitorAction = new UpdatePreregisterVisitorAction;

        return $preregisterVisitorAction->execute($request, $preregister_visitor);
    }
}
