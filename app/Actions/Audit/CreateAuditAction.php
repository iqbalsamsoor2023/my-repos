<?php

namespace App\Actions\Audit;

use App\Exceptions\GeneralException;
use App\Models\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreateAuditAction
{
    public function execute(Request $request)
    {
        $audit = Audit::create($request->only([
            'user_type',
            'user_id',
            'auditable_type',
            'auditable_id',
            'event',
            'old_values',
            'new_values',
            'url',
            'ip_address',
            'user_agent',
        ]));

        if (! $audit) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed recording an audit');
        }

        return $audit;
    }
}
