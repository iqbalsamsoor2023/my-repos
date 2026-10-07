<?php

namespace App\Filament\Resources\EmergencyContacts\Pages;

use App\Filament\Resources\EmergencyContacts\EmergencyContactResource;
use App\Models\DistrictEmergencyContact;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditEmergencyContact extends EditRecord
{
    protected static string $resource = EmergencyContactResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_emergency_contact');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationMessage(): ?string
    {
        return 'User updated';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $department_type = $data['department_type'];
        $list_departments = ['Hospital', 'Police', 'Foundation', 'Fire Station', 'Others'];

        if (in_array($department_type, $list_departments) == false) {
            $data['department_type'] = 'Others';
            $data['department_type_other'] = $this->record->department_type;
        }

        $district_emergency_contact = DistrictEmergencyContact::where('emergency_contact_id', $data['id'])->get()->pluck('thailand_district_id');
        $data['districtEmergencyContacts']['district'] = $district_emergency_contact->toArray();
        $data['coverage_mode'] = $this->record->getAttributes()['coverage_mode'];

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);

        if (isset($data['districtEmergencyContacts'])) {
            DistrictEmergencyContact::where('emergency_contact_id', $record->id)->delete();
            foreach ($data['districtEmergencyContacts']['district'] as $district) {
                DistrictEmergencyContact::create([
                    'emergency_contact_id' => $record->id,
                    'thailand_district_id' => $district,
                ]);
            }
        }

        return $record;
    }
}
