<?php

namespace App\Console\Commands\Visitor;

use App\Enums\ActivationModule\ModuleType;
use App\Models\ActivationModule;
use App\Models\Residence;
use Illuminate\Console\Command;

class CreateActivationModuleForExistingResidence extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'visitor:create-activation-vms-module';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Activation Visitor Setting Module for Existing Residence';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $residences = Residence::get();
        $bar = $this->output->createProgressBar($residences->count());
        $bar->start();

        foreach ($residences as $residence) {
            $existing_data = ActivationModule::whereIn('module', ['visitor', 'visitor slip'])->where('residence_id', $residence->id)->first();

            if (is_null($existing_data) == true) {
                $data = [
                    'residence_id' => $residence->id,
                ];

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor',
                    'module_type' => ModuleType::BRAND->value, // brand
                    'is_active' => 0,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor',
                    'module_type' => ModuleType::PURPOSE_OF_VISIT->value, // purpose of visit
                    'is_active' => 1,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor',
                    'module_type' => ModuleType::CONTACT_NUMBER->value, // contact number
                    'is_active' => 0,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor',
                    'module_type' => ModuleType::TEMPERATURE->value, // temperature
                    'is_active' => 0,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor',
                    'module_type' => ModuleType::COMPANY_NAME->value, // company name
                    'is_active' => 0,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor',
                    'module_type' => ModuleType::PASSENGER->value, // passenger
                    'is_active' => 0,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor',
                    'module_type' => ModuleType::REMARK->value, // remark
                    'is_active' => 0,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor',
                    'module_type' => ModuleType::PDPA->value, // pdpa
                    'is_active' => 0,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor',
                    'module_type' => ModuleType::COLOR->value, // color
                    'is_active' => 0,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor',
                    'module_type' => ModuleType::VISITOR_PHOTO->value, // visitor photo
                    'is_active' => 0,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor',
                    'module_type' => ModuleType::SCAN_VISITOR_CARD->value, // scan visitor card
                    'is_active' => 0,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor slip',
                    'module_type' => ModuleType::VS_VISITOR_CARD->value, // visitor card
                    'is_active' => 0,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor slip',
                    'module_type' => ModuleType::VS_VISITOR_NAME->value, // visitor name
                    'is_active' => 1,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor slip',
                    'module_type' => ModuleType::VS_VEHICLE_PLATE_NO->value, // vehicle plate no
                    'is_active' => 1,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor slip',
                    'module_type' => ModuleType::VS_PROVINCE_OF_VEHICLE->value, // province of vehicle
                    'is_active' => 1,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor slip',
                    'module_type' => ModuleType::VS_BRAND->value, // brand
                    'is_active' => 0,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor slip',
                    'module_type' => ModuleType::VS_COLOR->value, // color
                    'is_active' => 0,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor slip',
                    'module_type' => ModuleType::VS_PURPOSE_OF_VISIT->value, // purpose of visit
                    'is_active' => 1,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor slip',
                    'module_type' => ModuleType::VS_VEHICLE_TYPE->value, // type of vehicle
                    'is_active' => 1,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor slip',
                    'module_type' => ModuleType::VS_CONTACT_AT->value, // contact at
                    'is_active' => 1,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor slip',
                    'module_type' => ModuleType::VS_CONTACT_NUMBER->value, // contact number
                    'is_active' => 0,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor slip',
                    'module_type' => ModuleType::VS_TEMPERATURE->value, // temperature
                    'is_active' => 0,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor slip',
                    'module_type' => ModuleType::VS_COMPANY_NAME->value, // company name
                    'is_active' => 0,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor slip',
                    'module_type' => ModuleType::VS_PASSENGER->value, // passenger
                    'is_active' => 0,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor slip',
                    'module_type' => ModuleType::VS_REMARK->value, // remark
                    'is_active' => 1,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor slip',
                    'module_type' => ModuleType::VS_STAMP->value, // sign/stamp
                    'is_active' => 1,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor slip',
                    'module_type' => ModuleType::VS_QR_SCAN_OUT->value, // QR Code scan out
                    'is_active' => 1,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor slip',
                    'module_type' => ModuleType::VS_MOOBAN_LOGO->value, // MooBan logo
                    'is_active' => 0,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor',
                    'module_type' => ModuleType::VEHICLE_PHOTO->value, // vehicle image
                    'is_active' => 1,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'parking',
                    'module_type' => ModuleType::PARKING_FEE->value, // parking fees
                    'is_active' => 1,
                ]));

                ActivationModule::create(array_merge($data, [
                    'module' => 'visitor',
                    'module_type' => ModuleType::FOOD_AND_PARCEL->value, // food & parcel
                    'is_active' => 1,
                ]));
            }
        }

        $bar->finish();

        return static::SUCCESS;
    }
}
