<?php

namespace App\Filament\Resources\VisitorCards\Pages;

use App\Filament\Resources\VisitorCards\VisitorCardResource;
use App\Helpers\VisitorHelper;
use Filament\Resources\Pages\CreateRecord;

class CreateVisitorCard extends CreateRecord
{
    protected static string $resource = VisitorCardResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['visitor_card_no'] = VisitorHelper::generateVisitorCardId($data['residence_id']);

        return $data;
    }
}
