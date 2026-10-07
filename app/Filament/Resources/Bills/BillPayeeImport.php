<?php

namespace App\Filament\Resources\Bills;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class BillPayeeImport implements ToCollection
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
                'home_id' => trim($row[0]),
                'house_unit' => trim($row[1]),
                'bill_reminder_no' => ! empty(($row[2])) ? trim($row[2]) : null,
                'expenses_type' => trim($row[3]),
                'amount' => trim($row[4]),
                'notification' => trim($row[5]),
                'remark' => ! empty(($row[6])) ? trim($row[6]) : null,
            ];
        }
    }

    public function getArray(): array
    {
        return $this->data;
    }
}
