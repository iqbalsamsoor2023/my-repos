<?php

namespace App\Filament\Resources\Committees\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Committees\CommitteeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCommittees extends ListRecords
{
    protected static string $resource = CommitteeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('committee.create_committee')),
        ];
    }
}
