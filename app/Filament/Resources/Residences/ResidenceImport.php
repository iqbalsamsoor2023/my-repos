<?php

namespace App\Filament\Resources\Residences;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class ResidenceImport implements ToCollection
{
    private array $data = [];

    public function collection(Collection $rows)
    {
        $this->data = $rows->map(fn ($row) => [
            'name' => $row[0] ?? null,
            'name_th' => $row[1] ?? null,
            'mooban_type' => $row[2] ?? null,
            'sub_type' => $row[3] ?? null,
            'main_road' => $row[4] ?? null,
            'developer' => $row[5] ?? null,
            'completion_year' => $row[6] ?? null,
            'subscription_start' => $row[7] ?? null,
            'subscription_end' => $row[8] ?? null,
            'latitude' => $row[9] ?? null,
            'longitude' => $row[10] ?? null,
            'subdistrict' => $row[11] ?? null,
            'status' => $row[12] ?? null,
            'internet_provider' => $row[13] ?? null,
            'guard_house_entry' => $row[14] ?? null,
            'entry_lane_type' => $row[15] ?? null,
            'property_management_type' => $row[16] ?? null,
            'property_management' => $row[17] ?? null,
            'pm_expiry_date' => $row[18] ?? null,
            'security_guard' => $row[19] ?? null,
            'security_expiry_date' => $row[20] ?? null,
            'insurance' => $row[21] ?? null,
            'insurance_expiry_date' => $row[22] ?? null,
            'residence_company' => $row[23] ?? null,
            'support_ticket_status' => $row[24] ?? null,
            'has_receptionist' => $row[25] ?? null,
        ])->toArray();
    }

    public function getArray(): array
    {
        return $this->data;
    }
}
