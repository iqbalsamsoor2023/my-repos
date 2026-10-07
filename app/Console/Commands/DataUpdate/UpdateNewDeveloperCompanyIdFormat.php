<?php

namespace App\Console\Commands\DataUpdate;

use App\Models\Company;
use App\Models\Erp\CdpCompany;
use App\Models\Residence;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateNewDeveloperCompanyIdFormat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-new-developer-company-id-format';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update sgoc_company_id based on developer_id mapping from Company to CDP Company';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            DB::beginTransaction();

            $totalResidences = Residence::whereNotNull('developer_id')->count();
            $bar = $this->output->createProgressBar($totalResidences);

            Residence::whereNotNull('developer_id')
                ->chunk(200, function ($residences) use ($bar) {
                    foreach ($residences as $residence) {
                        $sgCompany = Company::find($residence->developer_id);

                        if ($sgCompany) {
                            $company = CdpCompany::where('name', $sgCompany->name)->first();

                            if ($company) {
                                $residence->update([
                                    'developer_id' => $company->id,
                                ]);
                            } else {
                                Log::warning("CDP Company not found for: {$sgCompany->name}");
                            }
                        } else {
                            Log::warning("Company not found for Residence ID: {$residence->id}");
                        }

                        $bar->advance();
                    }
                });

            $bar->finish();
            $this->newLine();

            DB::commit();

            $this->info('Processing complete!');

            return Command::SUCCESS;
        } catch (Exception $ex) {
            DB::rollBack();
            $this->error("An error occurred: {$ex->getMessage()}");

            return Command::FAILURE;
        }
    }
}
