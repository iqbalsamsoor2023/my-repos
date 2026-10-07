<?php

namespace App\Filament\Resources\EmergencyContacts\Pages;

use App\Filament\Resources\EmergencyContacts\EmergencyContactResource;
use App\Models\DistrictEmergencyContact;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateEmergencyContact extends CreateRecord
{
    protected static string $resource = EmergencyContactResource::class;

    public function getTitle(): string
    {
        return __('menu.create_emergency_contact');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        if ($data['department_type'] == 'Others') {
            $data['department_type'] = $data['department_type_other'];
        }

        $emergency_contact = static::getModel()::create($data);
        if (isset($data['districtEmergencyContacts'])) {
            foreach ($data['districtEmergencyContacts']['district'] as $district) {
                DistrictEmergencyContact::create([
                    'emergency_contact_id' => $emergency_contact->id,
                    'thailand_district_id' => $district,
                ]);
            }
        }

        return $emergency_contact;
    }
}
