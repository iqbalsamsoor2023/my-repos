<?php

namespace App\Actions\Report;

use App\Http\Integrations\ReportMicroservice\Report\Requests\ReportExportByDateRequest;

class ReportExportByDateAction
{
    public function execute(array $request)
    {
        $request = new ReportExportByDateRequest($request);
        $response = $request->send();

        return $response->json();
    }
}
