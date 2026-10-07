<?php

namespace App\Filament\Resources\Units;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class UnitImport implements ToCollection
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
                'block' => $row[1] ?? null,
                'street' => $row[2] ?? null,
                'floor' => $row[3] ?? null,
                'status' => $row[4] ?? null,
                'unit_size' => $row[5] ?? null,
                'land_size' => $row[6] ?? null,
                'move_at' => $row[7] ?? null,
                'myseevr_link' => $row[8] ?? null,
                'property_type' => $row[9] ?? null,
                'house_type' => $row[10] ?? null,
            ];
        }
    }

    public function getArray(): array
    {
        return $this->data;
    }
}
