<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserExport extends DefaultValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithStyles
{
    protected Collection $records;

    protected array $columns;

    public function __construct(Collection $records, array $columns)
    {
        $this->records = $records;
        $this->columns = $columns;
    }

    protected array $columnLabels = [
        'name' => 'Name (ชื่อ)',
        'email' => 'Email (อีเมล์)',
        'phone_no' => 'Phone Number (หมายเลขโทรศัพท์)',
        'roles.name' => 'Roles (บทบาท)',
        'email_verified_at' => 'Is Verified (อนุมัติ)',
        'pdpa_consent_status' => 'PDPA Consent Status (สถานะ PDPA)',
        'pdpa_agreed_at' => 'PDPA Agreed At (อัปเดตล่าสุด PDPA)',
        'created_at_date' => 'Created At Date (สร้างเมื่อวันที่)',
        'created_at_time' => 'Created At Time (สร้างเมื่อเวลา)',
    ];

    public function collection()
    {
        // Check if $records is indeed a Collection and has items
        if ($this->records instanceof Collection) {
            return $this->records->map(function ($record) {
                $data = [
                    'name' => $record?->name,
                    'email' => $record?->email,
                    'phone_no' => $record?->phone_no,
                    'roles.name' => $record?->roles->pluck('name')->implode(', '),
                    'email_verified_at' => is_null($record->email_verified_at) == false ? Carbon::parse($record->email_verified_at)->format('d-M-Y H:i:s') : '-',
                    'pdpa_consent_status' => $record->pdpa_agreed_at ? 'Agreed' : 'Pending',
                    'pdpa_agreed_at' => is_null($record->pdpa_agreed_at) == false ? Carbon::parse($record->pdpa_agreed_at)->format('d-M-Y H:i:s') : '-',
                    'created_at_date' => $record->created_at->format('d-M-Y'),
                    'created_at_time' => $record->created_at->format('H:i:s'),
                ];

                return collect($data)->only($this->columns);
            });
        }

        return collect(); // Fallback if records is not a collection
    }

    public function headings(): array
    {
        return array_map(function ($column) {
            return $this->columnLabels[$column] ?? $column; // fallback to raw key if no label
        }, $this->columns);
    }

    public function styles(Worksheet $sheet)
    {
        // Applying style to the header row
        $headerRow = 1;

        // Set background color to #003060 (dark blue)
        $sheet->getStyle('A'.$headerRow.':'.$sheet->getHighestColumn().$headerRow)
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('003060');

        // Set text color to white for header
        $sheet->getStyle('A'.$headerRow.':'.$sheet->getHighestColumn().$headerRow)
            ->getFont()
            ->getColor()
            ->setRGB('FFFFFF');

        // Set bold and center alignment for the headers
        $sheet->getStyle('A'.$headerRow.':'.$sheet->getHighestColumn().$headerRow)
            ->getFont()
            ->setBold(true);

        // Set center alignment for all header cells
        $sheet->getStyle('A'.$headerRow.':'.$sheet->getHighestColumn().$headerRow)
            ->getAlignment()
            ->setHorizontal('center')
            ->setVertical('center');
    }

    public function bindValue(Cell $cell, $value)
    {
        if (is_numeric($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
