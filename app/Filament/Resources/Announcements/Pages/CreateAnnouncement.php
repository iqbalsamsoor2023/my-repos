<?php

namespace App\Filament\Resources\Announcements\Pages;

use App\Actions\Announcement\CreateAnnouncementAction;
use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Models\AnnouncementUnit;
use App\Models\Residence;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;

    public function getTitle(): string
    {
        return __('menu.create_announcement');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        if (isset($data['all_residence']) == true && $data['all_residence'] == true) {
            $residences = Residence::select('id', 'name')->where('developer_user_id', Auth::user()->id)->get();

            foreach ($residences as $residence) {
                $announcement = static::getModel()::create(array_merge($data, [
                    'residence_id' => $residence->id,
                ]));

                if ($announcement && $announcement->is_active == true) {
                    $this->toDatabase($announcement);
                }
            }
        } elseif (isset($data['all_residence']) == true && $data['all_residence'] == false) {
            foreach ($data['residences_id'] as $residence_id) {
                $announcement = static::getModel()::create(array_merge($data, [
                    'residence_id' => $residence_id,
                ]));

                if ($announcement && $announcement->is_active == true) {
                    $this->toDatabase($announcement);
                }
            }
        } else {
            $announcement = static::getModel()::create($data);

            if (empty($data['unit']) == false) {
                foreach ($data['unit'] as $unit) {
                    AnnouncementUnit::create([
                        'announcement_id' => $announcement->id,
                        'unit_id' => $unit,
                    ]);
                }
            }

            if ($announcement && $announcement->is_active == true) {
                $this->toDatabase($announcement);
            }
        }

        return $announcement;
    }

    public function toDatabase($announcement)
    {
        $announcement_action = new CreateAnnouncementAction;

        return $announcement_action->sendAnnouncementCreatedNotification($announcement);
    }
}
