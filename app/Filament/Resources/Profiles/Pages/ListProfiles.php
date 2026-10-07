<?php

namespace App\Filament\Resources\Profiles\Pages;

use App\Filament\Resources\Profiles\ProfileResource;
use Filament\Resources\Pages\ListRecords;

class ListProfiles extends ListRecords
{
    protected static string $resource = ProfileResource::class;

    public function mount(): void
    {
        abort(403);
    }
}
