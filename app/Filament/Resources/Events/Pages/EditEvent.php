<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use App\Models\Unit;
use App\Models\UnitUser;
use App\Notifications\EventUpdated;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Notification;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_event');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        $units = Unit::where('residence_id', $this->record->residence_id)->get();

        if ($units->count() > 0) {
            $unit_ids = $units->pluck('id');
            $unitUsers = UnitUser::whereIn('unit_id', $unit_ids)->get()->unique('user_id');
            if ($unitUsers->count() > 0) {
                foreach ($unitUsers as $unitUser) {
                    Notification::send($unitUser->user, new EventUpdated($this->record, $unitUser));
                }
            }
        }
    }
}
