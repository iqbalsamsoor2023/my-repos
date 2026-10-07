<?php

namespace App\Filament\Resources\Profiles\Pages;

use App\Filament\Resources\Profiles\ProfileResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditProfile extends EditRecord
{
    protected static string $resource = ProfileResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_profile');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Ensure user can only edit their own profile
        if ($this->record->id !== Auth::id()) {
            abort(403, 'You are not authorized to edit this profile');
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.pages.dashboard');
    }
}
