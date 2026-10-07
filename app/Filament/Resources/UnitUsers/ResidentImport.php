<?php

namespace App\Filament\Resources\UnitUsers;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class ResidentImport implements ToCollection
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
                'unit_number' => $row[0] ?? null,
                'user' => $row[1] ?? null,
                'relationship' => $row[2] ?? null,
                'is_owner' => $row[3] ?? null,
                'is_main_tenant' => $row[4] ?? null,
                'email' => $row[5] ?? null,
                'phone_no' => $row[6] ?? null,
            ];
        }
    }

    public function getArray(): array
    {
        return $this->data;
    }
}
