<?php

namespace App\Filament\Resources\Checkpoints\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Checkpoints\CheckpointResource;
use Filament\Resources\Pages\EditRecord;

class EditCheckpoint extends EditRecord
{
    protected static string $resource = CheckpointResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_checkpoint');
    }

    protected function getActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
