<?php

namespace App\Filament\Resources\OtherAmenities\Pages;

use App\Filament\Resources\OtherAmenities\OtherAmenityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOtherAmenity extends CreateRecord
{
    protected static string $resource = OtherAmenityResource::class;

    protected static ?string $title = 'New warranty';
}
