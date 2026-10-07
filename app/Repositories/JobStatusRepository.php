<?php

namespace App\Repositories;

use App\Actions\JobStatus\JobStatusGetIndexAction;

class JobStatusRepository
{
    public function index(array $request)
    {
        $jobsStatusAction = new JobStatusGetIndexAction;
        $jobStatuses = $jobsStatusAction->execute($request);

        return $jobStatuses;
    }
}
