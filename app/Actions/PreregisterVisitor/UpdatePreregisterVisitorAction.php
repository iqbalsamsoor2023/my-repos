<?php

namespace App\Actions\PreregisterVisitor;

use App\Exceptions\GeneralException;
use App\Http\Requests\PreregisterVisitor\UpdatePreregisterVisitorRequest;
use App\Models\PreregisterVisitor;
use Illuminate\Http\JsonResponse;

class UpdatePreregisterVisitorAction
{
    public function execute(UpdatePreregisterVisitorRequest $request, PreregisterVisitor $preregister_visitor)
    {
        $preregister_visitor = $this->updatePreregisterVisitor($request, $preregister_visitor);

        return $preregister_visitor;
    }

    private function updatePreregisterVisitor(UpdatePreregisterVisitorRequest $request, PreregisterVisitor $preregister_visitor)
    {
        $preregister_visitor->update($request->only([
            'is_qr_code_expired',
        ]));

        if (! $preregister_visitor) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed updating pre-register visitor');
        }

        return $preregister_visitor;
    }
}
