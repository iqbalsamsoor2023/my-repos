<?php

namespace App\Console\Commands\DataMigrations\Erp;

use Exception;
use App\Enums\Company\CompanyTypeEnum;
use App\Models\Company;
use App\Models\Erp\CdpCompany;
use App\Models\Erp\Contact;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PropertyManagementCompanyData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:pm-company-data-migration-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate Property Management Company Data to CDP Company in ERP';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = Company::where('type', 'Property Management');
        $bar = $this->output->createProgressBar($query->count());
        $bar->start();

        $companies = $query->get();
        foreach ($companies as $company) {
            $cross_data_checking = CdpCompany::where('company_category_id', CompanyTypeEnum::PROPERTY_MANAGEMENT->value)->where('name', $company->name)->first();

            if (! $cross_data_checking) {
                $user = User::withTrashed()->whereId($company->user_id)->first();

                DB::beginTransaction();

                try {
                    $cdpCompany = CdpCompany::create([
                        'company_category_id' => CompanyTypeEnum::PROPERTY_MANAGEMENT->value,
                        'name' => $company->name,
                        'name_th' => $company->name_th,
                        'province_id' => is_null($company->province_id) ? 1 : $company->province_id,
                        'billing_address' => $company->address,
                        'delivery_address' => null,
                        'contact_number' => $company->contact_number,
                        'contact_email' => $company->contact_email,
                        'activation_status_id' => 5,
                        'social_media' => [
                            'instagram' => null,
                            'tiktok_link' => null,
                            'website_url' => $company->website_url,
                            'facebook_link' => null,
                        ],
                        'custom_attributes' => [
                            'pmoc_user_id' => isset($user) ? $user->id : null,
                        ],
                        'created_at' => $company->created_at,
                        'updated_at' => $company->updated_at,
                        'deleted_at' => $company->deleted_at,
                    ]);

                    if (! is_null($company->person_in_charges) && $cdpCompany) {
                        foreach ($company->person_in_charges as $person_in_charge) {
                            Contact::create([
                                'contactable_type' => 'App\Models\CdpCompany',
                                'contactable_id' => $cdpCompany->id,
                                'company_category_id' => CompanyTypeEnum::PROPERTY_MANAGEMENT->value,
                                'contact_details' => [
                                    'name' => $person_in_charge['name'],
                                    'name_th' => $person_in_charge['name'],
                                    'mobile_number' => $person_in_charge['contact'],
                                    'line_id' => '-',
                                    'email' => $person_in_charge['email'],
                                ],
                            ]);
                        }
                    }

                    DB::commit();
                } catch (Exception $ex) {
                    DB::rollback();
                    throw $ex;
                }
            } else {
                $cross_data_checking->update([
                    'mmb_user_id' => $company->user_id,
                ]);
            }
            $bar->advance();
        }

        $bar->finish();

        return Command::SUCCESS;
    }
}
