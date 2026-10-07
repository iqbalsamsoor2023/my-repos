<?php

namespace App\Filament\Resources\IncidentReports\Pages;

use App\Filament\Resources\IncidentReports\IncidentReportResource;
use App\Models\Unit;
use Filament\Resources\Pages\ViewRecord;

class ViewIncidentReport extends ViewRecord
{
    protected static string $resource = IncidentReportResource::class;

    public function getTitle(): string
    {
        return __('menu.view_incident_report');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (is_null($data['mmb_unit_id']) == false) {
            $unit = Unit::where('id', $data['mmb_unit_id'])->first();
            $residence = $unit->residence->id;

            $data['residence_id'] = $residence;
        } else {
            $data['residence_id'] = $data['mmb_residence_id'];
        }

        return $data;
    }
}
