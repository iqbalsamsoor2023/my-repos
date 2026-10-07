<?php

namespace App\Actions\Report;

use App\Http\Integrations\ReportMicroservice\Report\Requests\ReportExportRequest;

class ReportExportAction
{
    public function execute(array $request)
    {
        $request = new ReportExportRequest($request);
        $response = $request->send();

        return $response->json();
    }
}
