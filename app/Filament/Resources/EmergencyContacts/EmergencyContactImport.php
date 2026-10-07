<?php

namespace App\Filament\Resources\EmergencyContacts;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class EmergencyContactImport implements ToCollection
{
    private $data;

    public function __construct()
    {
        $this->data = [];
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $this->data[] = [
                'department_type' => $row[0] ?? null,
                'name' => $row[1] ?? null,
                'contact_no' => $row[2] ?? null,
                'coverage_mode' => $row[3] ?? null,
                'district' => $row[4] ?? null,
                'is_active' => $row[5] ?? null,
            ];
        }
    }

    public function getArray(): array
    {
        return $this->data;
    }
}
