<?php

namespace App\Console\Commands;

use App\Console\Commands\DataMigrations\ModulesActivationData;
use App\Console\Commands\DataMigrations\PropertyManagementsData;
use App\Console\Commands\DataMigrations\UnitsData;
use App\Console\Commands\DataMigrations\UserHealthsData;
use App\Console\Commands\DataMigrations\UsersData;
use App\Console\Commands\ImageMigrations\UnitsImage;
use App\Console\Commands\ImageMigrations\UsersImage;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Benchmark;
use Illuminate\Support\Facades\Artisan;

class InactiveDataMigration extends Command implements Isolatable
{
    protected $usersData;

    protected $unitsData;

    protected $userHealthsData;

    protected $modulesActivationData;

    protected $usersImage;

    protected $unitsImage;

    protected $propertyManagementsData;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inactive-data-migration:run
    {--residence_id= : Only trigger for a particulat residence ("example: 2219)}'; // --residence_id=2219

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run data migration from MMB to MMB2.';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        UsersData $usersData,
        UnitsData $unitsData,
        UserHealthsData $userHealthsData,
        ModulesActivationData $modulesActivationData,
        PropertyManagementsData $propertyManagementsData,
        UsersImage $usersImage,
        UnitsImage $unitsImage,
    ) {
        $this->usersData = $usersData;
        $this->unitsData = $unitsData;
        $this->usersImage = $usersImage;
        $this->unitsImage = $unitsImage;
        $this->userHealthsData = $userHealthsData;
        $this->modulesActivationData = $modulesActivationData;
        $this->propertyManagementsData = $propertyManagementsData;

        parent::__construct($this);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $start = microtime(true);
        $residenceIds = $this->option('residence_id');

        if (is_string($residenceIds)) {
            $residence_ids = json_decode($residenceIds, true); // Decode JSON string
        }
        if (! empty($residence_ids)) {
            foreach ($residence_ids as $residence_id) {
                $migrations = [
                    'Migrate users table' => $this->usersData,
                    'Migrate users image' => $this->usersImage,
                    'Migrate units table' => $this->unitsData,
                    'Migrate units image' => $this->unitsImage,
                    'Migrate user health table' => $this->userHealthsData,
                    'Migrate modules activation table' => $this->modulesActivationData,
                    'Migrate property management table' => $this->propertyManagementsData,
                ];

                $bar = $this->output->createProgressBar(count($migrations));
                $bar->start();

                $benchmarks = []; // Reset benchmarks for each residence ID

                foreach ($migrations as $key => $migration) {
                    if (str_contains($key, 'Artisan')) {
                        $benchmarks[] = [$key, Benchmark::measure(fn () => Artisan::call($migration))];
                    } else {
                        $benchmarks[] = [$key, Benchmark::measure(fn () => $migration->execute($residence_id))];
                    }

                    $bar->advance();
                    $this->newLine();
                    $this->line($key);
                }

                $bar->finish();

                $this->newLine(1);
                $this->table(
                    ['Key', 'Benchmark (ms)'],
                    $benchmarks
                );
            }

            $time = microtime(true) - $start; // Measure total execution time for all residence IDs
            $this->newLine(1);
            $this->line('Total executed time: '.number_format($time, 2).' seconds');
            $this->newLine(1);
        } else {
            $this->info('No residence IDs provided.');
        }

        return Command::SUCCESS;
    }
}
