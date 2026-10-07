<?php

namespace App\Actions\BlacklistedVisitor;

use App\Models\BlacklistedVisitor;

class IsBlacklistedVisitorAction
{
    public function execute($request)
    {
        return $this->blacklistedChecker($request);
    }

    public function blacklistedChecker($request)
    {
        if (! is_numeric($request->is_allowed)) { // skip this checker after proved as a blacklisted visitor
            $is_blacklist = BlacklistedVisitor::with('visitor')
                ->where([
                    ['residence_id', $request->residence_id],
                    ['vehicle_plate_no', $request->vehicle_plate_no ?? 'not exists'],
                ])
                ->OrWhereHas('visitor', function ($query) use ($request) {
                    $query->where([['residence_id', $request->residence_id], ['id_number', $request->id_number]]) // for passport no
                        ->orWhere([['residence_id', $request->residence_id], ['name', $request->name]]);
                })
                ->first();

            if (isset($is_blacklist)) {
                return $is_blacklist;
            }

            if (! $is_blacklist) {
                $blacklisted_visitors = BlacklistedVisitor::where('residence_id', $request->residence_id)
                    ->get();

                if ($blacklisted_visitors->count() > 0) {
                    $visitorBlacklistID = [];

                    foreach ($blacklisted_visitors as $blacklisted_visitor) {
                        if (! empty($blacklisted_visitor->visitor->id_number) && $blacklisted_visitor->visitor->id_type == 1) {
                            $visitorBlacklistID[] = substr($blacklisted_visitor->id_number, -8);
                        }
                    }

                    $requestID = substr($request->id_number ?? '-', -8);

                    if (in_array($requestID, $visitorBlacklistID)) {
                        return BlacklistedVisitor::where('residence_id', $request->residence_id)
                            ->whereHas('visitor', function ($query) use ($request, $requestID) {
                                $query->where([['residence_id', $request->residence_id], ['id_number', 'like', '%'.$requestID.'%']]);
                            })
                            ->first();
                    }
                }
            }
        } elseif ($request->is_allowed == 1) {
            $request->merge(['blacklist_remark' => $request->blacklist_remark]);
        } elseif ($request->is_allowed == 0) {
            $request->merge(['blacklist_remark' => 'No Entry!']);
        }
    }
}
