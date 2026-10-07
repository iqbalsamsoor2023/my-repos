<?php

namespace App\Actions\VisitingArrangement;

use App\Exceptions\GeneralException;
use App\Models\UnitUser;
use App\Models\VisitingArrangement;
use Illuminate\Http\JsonResponse;

class GetVisitingArrangementAction
{
    public function execute($request)
    {
        $visitingArrangement = VisitingArrangement::with(
            'visitorLog:id,is_pre_register,visitor_purpose,vehicle_plate_no,arrival_time,leave_time,remark,blacklist_remark,visitor_id,visitor_code,arrival_type,vehicle_type',
            'visitorLog.visitor:id,name,contact_no,id_number',
            'unit:id,unit_number',
            'user:id,name',
            'estampBy:id,name',
            'visitorLog.visitingArrangements:id,visitor_log_id,residence_id,unit_id,user_id,estamp_by,estamp_by_type,status,feedback_remark',
            'visitorLog.visitingArrangements.estampBy:id,name,email,phone_no',
        );

        if (isset($request->visitor_log_id)) {
            $visitingArrangement = $visitingArrangement->where('visitor_log_id', $request->visitor_log_id);
        }

        if (isset($request->unit_id)) {
            $visitingArrangement = $visitingArrangement->where('unit_id', $request->unit_id);
        }

        if (isset($request->unit_user_id)) {
            $unitUser = UnitUser::whereId($request->unit_user_id)->first();

            if (is_null($unitUser) == true) {
                throw new GeneralException(JsonResponse::HTTP_NOT_FOUND, 'Not Found');
            }

            $request->merge([
                'user_id' => $unitUser->user_id,
            ]);
        }

        if (isset($request->user_id)) {
            $visitingArrangement = $visitingArrangement->where('user_id', $request->user_id);
        }

        $visitingArrangement = $visitingArrangement->orderBy('id', 'desc')->paginate($request->item_per_page ?? 10);

        return $visitingArrangement;
    }
}
