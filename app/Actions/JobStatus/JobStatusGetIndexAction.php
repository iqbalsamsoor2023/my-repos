<?php

namespace App\Actions\JobStatus;

use App\Http\Integrations\ReportMicroservice\JobStatus\Requests\JobStatusIndexRequest;

class JobStatusGetIndexAction
{
    public function execute(array $request)
    {
        $request = new JobStatusIndexRequest($request);
        $response = $request->send();

        return $response->json();
    }
}
