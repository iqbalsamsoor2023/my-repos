<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\AppVersion;
use Illuminate\Console\Command;

class MigrateAppVersionsToApplications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-app-versions-to-applications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate app versions to applications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration of app versions to applications...');

        $appVersions = AppVersion::select('platform', 'package_identifier', 'application_name')
            ->groupBy('platform', 'package_identifier', 'application_name')
            ->get();

        $bar = $this->output->createProgressBar(count($appVersions));
        $bar->setFormat(' %current%/%max% [%bar%] %percent%');
        $bar->setBarCharacter('<fg=green>=</>');
        $bar->setEmptyBarCharacter(' ');
        $bar->setProgressCharacter('>');
        $bar->setRedrawFrequency(1);

        foreach ($appVersions as $appVersion) {
            $application = [
                'platform' => $appVersion->platform,
                'package_identifier' => $appVersion->package_identifier,
                'application_name' => $appVersion->application_name,
            ];

            $insertedApplication = Application::firstOrCreate(
                $application,
                $application
            );

            // Update the app versions with the new application ID
            AppVersion::where('platform', $appVersion->platform)
                ->where('package_identifier', $appVersion->package_identifier)
                ->where('application_name', $appVersion->application_name)
                ->update(['application_id' => $insertedApplication->id]);

            $bar->advance();
        }

        $bar->finish();
        $this->line('');
        $this->info('App versions migrated to applications successfully.');
    }
}
