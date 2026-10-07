<?php

namespace App\Filament\Resources\PublicMaintenances\Pages;

use Filament\Actions\DeleteAction;
use App\Actions\Maintenance\SendMaintenanceUpdateNotification;
use App\Filament\Resources\PublicMaintenances\PublicMaintenanceResource;
use App\Models\ResidenceAmenity;
use App\Models\ResidenceAmenityOption;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPublicMaintenance extends EditRecord
{
    protected static string $resource = PublicMaintenanceResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_public_maintenance');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $type = $data['maintainable_type'] ?? null;
        $id = $data['maintainable_id'] ?? null;

        if ($type === ResidenceAmenityOption::class) {
            $data['maintainable_id'] = 'option-'.$id;
        } elseif ($type === ResidenceAmenity::class) {
            $data['maintainable_id'] = 'amenity-'.$id;
        }

        if ($type === ResidenceAmenityOption::class) {
            $facility = ResidenceAmenityOption::with('residenceAmenity')->find($id);
            $data['residence_id'] = optional($facility->residenceAmenity)->residence_id;
        } elseif ($type === ResidenceAmenity::class) {
            $facility = ResidenceAmenity::find($id);
            $data['residence_id'] = optional($facility)->residence_id;
        }

        $data['status'] = $this->record->getAttributes()['status'];
        $data['is_verified'] = $this->record->getAttributes()['is_verified'];
        $data['claimable_title_id'] = $data['claimable_item_details']['id'] ?? null;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);

        $sendMaintenanceUpdateNotification = new SendMaintenanceUpdateNotification;
        $sendMaintenanceUpdateNotification->execute($record);

        return $record;
    }
}
