<?php

namespace App\Actions\Report;

use App\Models\Report;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GetReportAction
{
    public function execute(Request $request)
    {
        $residence = Auth::user()->propertyManagement;

        // Can accept PGS, IRS, VMS only
        $module = $request->query('module');

        // Up to 3 months only. ie: March 2023
        if (empty($request->query('month_year'))) {
            $monthYear = new Carbon;
        } else {
            $monthYear = new Carbon($request->query('month_year'));
        }

        $maxDateLimit = new Carbon('3 months ago');

        $reports = Report::where('residence_id', $residence->id)
            ->where('module', $module)
            ->whereMonth('report_date', "$monthYear->month")
            ->whereYear('report_date', "$monthYear->year")
            ->whereDate('report_date', '>=', $maxDateLimit)
            ->latest('report_date');

        if (isset($request->format)) {
            $reports = $reports->where('format', $request->format);
        }

        return $reports->get();
    }
}
