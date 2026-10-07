<?php

namespace App\Filament\Resources\Announcements\Pages;

use App\Actions\Announcement\CreateAnnouncementAction;
use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Models\AnnouncementUnit;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditAnnouncement extends EditRecord
{
    protected static string $resource = AnnouncementResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_announcement');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $announcement_unit = AnnouncementUnit::where('announcement_id', $data['id'])->get()->pluck('unit_id');
        $data['unit'] = $announcement_unit->toArray();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $originalIsActive = $record->is_active;
        $record->update($data);

        if (empty($data['unit']) == false) {
            AnnouncementUnit::where('announcement_id', $record->id)->delete();
            foreach ($data['unit'] as $unit) {
                AnnouncementUnit::create([
                    'announcement_id' => $record->id,
                    'unit_id' => $unit,
                ]);

                // if is_active is changed from false to true, send notification
                if ($originalIsActive == false && isset($data['is_active']) && $data['is_active'] == true) {
                    $this->toDatabase($record);
                }
            }
        }

        return $record;
    }

    public function toDatabase($announcement)
    {
        $announcement_action = new CreateAnnouncementAction;

        return $announcement_action->sendAnnouncementCreatedNotification($announcement);
    }
}
