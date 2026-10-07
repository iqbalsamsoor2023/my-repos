<?php

namespace App\Listeners;

use App\Enums\ActivationModule\ModuleType;
use App\Events\ResidenceCreated;
use App\Models\ActivationModule;

class GenerateActivationSetting
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param ResidenceCreated $event
     * @return void
     */
    public function handle(ResidenceCreated $event)
    {
        $residence = $event->residence;

        $this->createActivationModule($residence);
    }

    private function createActivationModule($residence): void
    {
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
            'module' => 'visitor',
            'module_type' => ModuleType::PARKING->value, // parking button
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
