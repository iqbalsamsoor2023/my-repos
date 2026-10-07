<?php

namespace App\Actions\Visitor;

use App\Http\Integrations\ReportMicroservice\Visitor\Requests\VisitorReportExportRequest;

class ExportVisitorLogAction
{
    public function execute(array $request)
    {
        $request = new VisitorReportExportRequest($this->visitorLogData($request));
        $response = $request->send();

        return $response->json();
    }

    private function visitorLogData(array $request)
    {
        $data = [];

        if (!empty($request['language'])) {
            $data['language'] = $request['language'];
        }

        if (!empty($request['project'])) {
            $data['project'] = $request['project'];
        }

        if (!empty($request['format'])) {
            $data['format'] = $request['format'];
        }

        if (!empty($request['user_id'])) {
            $data['user_id'] = $request['user_id'];
        }

        if (!empty($request['date_from'])) {
            $data['date_from'] = $request['date_from'];
        }

        if (!empty($request['date_until'])) {
            $data['date_until'] = $request['date_until'];
        }

        if (isset($request['residence_ids'])) {
            $data['residence_ids'] = $request['residence_ids'] ? json_encode($request['residence_ids']) : null;
        }

        if (empty($request['ids']) == false) {
            $data['ids'] = json_encode($request['ids']);
        }

        if (!empty($request['data_type'])) {
            $data['data_type'] = $request['data_type'];
        }

        return $data;
    }
}
