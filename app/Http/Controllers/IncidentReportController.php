<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Exports\IncidentReports\ExportIncidentReport;
use App\Services\SgocGraphQLService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class IncidentReportController extends Controller
{
    /**
     * Export Incident Report Data
     *
     * @param  Request  $request
     * @return BinaryFileResponse
     */
    public function export(Request $request)
    {
        $inputs = $request->all();
        $data = $this->getDataExport($inputs);

        return Excel::download(new ExportIncidentReport($data), 'MyMooBan Incident Report.xlsx');
    }

    private function getDataExport($request)
    {
        $query = <<<'GQL'
        query($page: Int!, $title: String) {
            paginatedIncidentReports(
                page: $page,
                title: $title,
                orderBy: [
                    {
                    column: "created_at"
                    order: DESC
                    }
                ]) {
                data {
                    id
                    mmb_unit_id
                    title
                    description
                    image_url
                    created_at
                    createdBy {
                        id
                        name
                    }
                }
                paginatorInfo {
                    count
                    currentPage
                    hasMorePages
                    total
                }
            }
        }
        GQL;

        $variables['page'] = is_null($request['page']) == false ? (int) $request['page'] : 1;

        if (is_null($request['title']) == false) {
            $variables['title'] = ('%'.$request['title'].'%');
        }

        $response = SgocGraphQLService::execute($query, $variables);
        $incidentReports = collect(data_get($response->json(), 'data.paginatedIncidentReports'));

        return $incidentReports;
    }
}
