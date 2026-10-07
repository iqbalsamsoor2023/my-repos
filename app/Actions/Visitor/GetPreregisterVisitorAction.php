<?php

namespace App\Actions\Visitor;

use App\Exceptions\GeneralException;
use App\Models\PreregisterVisitor;
use App\Models\Sgoc\User as SgocUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetPreregisterVisitorAction
{
    public function execute(Request $request)
    {
        $preregisterVisitors = PreregisterVisitor::with('visitor', 'unit');

        if (isset($request->unit_id)) {
            $preregisterVisitors = $preregisterVisitors->where('unit_id', $request->unit_id);
        }

        if (isset($request->user_id)) {
            $preregisterVisitors = $preregisterVisitors->where('user_id', $request->user_id);
        }

        if (isset($request->visitor_code)) {
            $preregisterVisitors = $preregisterVisitors->where('visitor_code', $request->visitor_code);
        }

        if (isset($request->is_qr_code_expired)) {
            $preregisterVisitors = $preregisterVisitors->where('is_qr_code_expired', $request->is_qr_code_expired);
        }

        if (isset($request->has_pagination) && ($request->has_pagination == false)) {
            return $preregisterVisitors->orderBy('id', 'desc')->get();
        }

        if (isset($request->visitor_code) && isset($request->sg_user_id)) {
            $preregisterVisitors = $preregisterVisitors->where('visitor_code', $request->visitor_code);
            $sgocCompanyId = $preregisterVisitors->first()?->unit?->residence->sgoc_company_id;

            $sgocUser = SgocUser::findOrFail($request->sg_user_id);

            if ($sgocUser->company_id !== $sgocCompanyId) {
                throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'QR Invalid');
            }
        }

        $preregisterVisitors = $preregisterVisitors->orderBy('id', 'desc')->paginate($request->item_per_page ?? 10);

        return $preregisterVisitors;
    }
}
