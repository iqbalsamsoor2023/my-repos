<?php

namespace App\Jobs;

use App\Imports\PrivateClaimSettingsImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Maatwebsite\Excel\Facades\Excel;
use Log;

class ImportPrivateClaimSettingsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public $filePath) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Excel::import(new PrivateClaimSettingsImport, $this->filePath);
    }
}