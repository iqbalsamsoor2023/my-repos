<?php

namespace App\Filament\Resources\Committees\Pages;

use Filament\Actions\DeleteAction;
use Carbon\Carbon;
use App\Filament\Resources\Committees\CommitteeResource;
use Filament\Resources\Pages\EditRecord;

class EditCommittee extends EditRecord
{
    protected static string $resource = CommitteeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['user_ids'] = isset($data['user_id']) ? [(int) $data['user_id']] : [];

        // Convert date fields to month/year selects for editing
        if (isset($data['term_start'])) {
            $date = Carbon::parse($data['term_start']);
            $data['term_start_month'] = $date->month;
            $data['term_start_year'] = $date->year;
        }

        if (isset($data['term_end'])) {
            $date = Carbon::parse($data['term_end']);
            $data['term_end_month'] = $date->month;
            $data['term_end_year'] = $date->year;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Convert month/year selects back to date fields
        if (isset($data['term_start_month']) && isset($data['term_start_year'])) {
            $data['term_start'] = sprintf(
                '%04d-%02d-01',
                $data['term_start_year'],
                $data['term_start_month']
            );
            unset($data['term_start_month'], $data['term_start_year']);
        }

        if (isset($data['term_end_month']) && isset($data['term_end_year'])) {
            $data['term_end'] = sprintf(
                '%04d-%02d-01',
                $data['term_end_year'],
                $data['term_end_month']
            );
            unset($data['term_end_month'], $data['term_end_year']);
        } else {
            $data['term_end'] = null;
            unset($data['term_end_month'], $data['term_end_year']);
        }

        return $data;
    }
}
