<?php

namespace App\Http\Controllers\Api\V1\Reports\PGS;

use App\Http\Controllers\Controller;
use App\Http\Resources\DailyReportCollection;
use App\Repositories\ReportRepository;
use Illuminate\Http\Request;

class DailyReportController extends Controller
{
    protected $reportRepository;

    public function __construct(ReportRepository $reportRepository)
    {
        $this->reportRepository = $reportRepository;
    }

    public function index(Request $request)
    {
        $response = $this->reportRepository->index($request);

        return new DailyReportCollection($response);
    }
}
