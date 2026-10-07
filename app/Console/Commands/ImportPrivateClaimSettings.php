<?php

namespace App\Console\Commands;

use App\Jobs\ImportPrivateClaimSettingsJob;
use Illuminate\Console\Command;

class ImportPrivateClaimSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:private-claim-settings {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');

        ImportPrivateClaimSettingsJob::dispatch($filePath);

        $this->info('Job dispatched successfully.');
    }
}