<?php

namespace App\Filament\Resources\Visitors\Pages;

use App\Filament\Resources\Visitors\VisitorResource;
use App\Models\PreregisterVisitor;
use App\Models\VisitorLog;
use App\Models\VisitorLogArchive;
use App\Models\VisitingArrangement;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ViewVisitor extends ViewRecord
{
    protected static string $resource = VisitorResource::class;

    public function getTitle(): string
    {
        return __('menu.view_visitor');
    }

    /**
     * Get the visitor log record.
     */
    public function getRecord(): Model
    {
        // Return the VisitorLog record directly
        return parent::getRecord();
    }

    protected function resolveRecord(int | string $key): Model
    {
        try {
            return parent::resolveRecord($key);
        } catch (ModelNotFoundException $exception) {
            $record = VisitorLogArchive::query()->find($key);

            if (! $record) {
                throw $exception;
            }

            return $record;
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Get the actual VisitorLog record
        $record = $this->getRecord();

        if ($record instanceof VisitorLog || $record instanceof VisitorLogArchive) {
            $data['residence_id'] = $record->residence_id;

            if ($record->preregisterVisitor) {
                $data['validity_start_date'] = $record->preregisterVisitor->validity_start_date;
                $data['validity_end_date'] = $record->preregisterVisitor->validity_end_date;
                $data['is_multiple_entry'] = $record->preregisterVisitor->is_multiple_entry;
            }
        }

        return $data;
    }
}
