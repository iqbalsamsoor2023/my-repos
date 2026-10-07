<?php

namespace App\Console\Commands\OneTime;

use App\Enums\Company\InsuranceTypeEnum;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateInsuranceCompanies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-insurance-companies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate insurance-related companies to insurance_companies table';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info("Starting migration of insurance companies...\n");

        // Fetch companies with type like "%Insurance%"
        $companies = DB::table('companies')
            ->where('type', 'LIKE', '%Insurance%')
            ->get();

        $count = $companies->count();

        if ($count === 0) {
            $this->info('No insurance companies found to migrate.');

            return;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $inserted = 0;

        foreach ($companies as $company) {
            // Map type
            $type = match (strtolower($company->type)) {
                'life insurance' => InsuranceTypeEnum::LIFE_INSURANCE->value,
                'vehicle insurance' => InsuranceTypeEnum::VEHICLE_INSURANCE->value,
                'fire insurance' => InsuranceTypeEnum::HOUSE_INSURANCE->value,
                default => null,
            };

            if (! $type) {
                $bar->advance();

                continue; // skip if not a recognized insurance type
            }

            DB::table('insurance_companies')->insert([
                'name' => $company->name,
                'type' => $type,
                'website_url' => $company->website_url ?? null,
                'is_active' => $company->is_active ?? 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $inserted++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Migration complete. $inserted companies migrated to insurance_companies.");
    }
}
