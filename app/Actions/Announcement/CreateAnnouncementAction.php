<?php

namespace App\Actions\Announcement;

use App\Exceptions\GeneralException;
use App\Http\Requests\Announcement\StoreAnnouncementRequest;
use App\Models\Announcement;
use App\Models\AnnouncementUnit;
use App\Models\Unit;
use App\Models\UnitUser;
use App\Notifications\AnnouncementCreated;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Notification;

class CreateAnnouncementAction
{
    public function execute(StoreAnnouncementRequest $request)
    {
        $announcement = Announcement::create($request->only([
            'residence_id',
            'title',
            'description',
            'is_active',
            'created_by',
            'updated_by',
        ]));

        if (! $announcement) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed creating announcement');
        }

        $this->uploadImages($announcement, $request);
        $this->uploadAttachment($announcement, $request);

        if ($announcement->is_active == true) {
            $this->sendAnnouncementCreatedNotification($announcement);
        }

        return $announcement;
    }

    private function uploadImages($announcement, $request)
    {
        if ($request->hasFile('images')) {
            $announcement->addMultipleMediaFromRequest(['images'])
                ->each(function ($fileAdded) {
                    $fileAdded->withCustomProperties(['type' => 'image'])->toMediaCollection('images');
                });
        }
    }

    private function uploadAttachment($announcement, $request)
    {
        if ($request->hasFile('attachment')) {
            $announcement->addMediaFromRequest('attachment')->withCustomProperties(['type' => 'document'])->toMediaCollection('document');
        }
    }

    public function sendAnnouncementCreatedNotification($announcement)
    {
        $announcement_units = AnnouncementUnit::where('announcement_id', $announcement->id)->get();

        if ($announcement_units->count() > 0) {
            $unit_ids = $announcement_units->pluck('unit_id');
            $unitUsers = UnitUser::whereIn('unit_id', $unit_ids)->get()->unique('user_id');

            foreach ($unitUsers as $unitUser) {
                Notification::send($unitUser->user, new AnnouncementCreated($announcement, $unitUser));
            }
        } else {
            $unit_ids = Unit::where('residence_id', $announcement->residence_id)->pluck('id');
            $unitUsers = UnitUser::whereIn('unit_id', $unit_ids)->get()->unique('user_id');

            foreach ($unitUsers as $unitUser) {
                Notification::send($unitUser->user, new AnnouncementCreated($announcement, $unitUser));
            }
        }
    }
}
