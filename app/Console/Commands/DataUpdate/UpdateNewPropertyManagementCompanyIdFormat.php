<?php

namespace App\Console\Commands\DataUpdate;

use App\Models\Company;
use App\Models\Erp\CdpCompany;
use App\Models\Residence;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateNewPropertyManagementCompanyIdFormat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-new-property-management-company-id-format';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update property_management_id mapping from Company in CDP Company';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            DB::beginTransaction();

            $totalResidences = Residence::whereNotNull('property_management_id')->count();
            $bar = $this->output->createProgressBar($totalResidences);

            Residence::whereNotNull('property_management_id')
                ->chunk(200, function ($residences) use ($bar) {
                    foreach ($residences as $residence) {
                        $pmCompany = Company::find($residence->property_management_id);

                        if ($pmCompany) {
                            $company = CdpCompany::where('name', $pmCompany->name)->first();

                            if ($company) {
                                $residence->update([
                                    'property_management_id' => $company->id,
                                ]);
                            } else {
                                Log::warning("CDP Company not found for: {$pmCompany->name}");
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
