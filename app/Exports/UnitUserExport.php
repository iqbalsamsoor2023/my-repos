<?php

namespace App\Exports;

use App\Enums\GeneralStatus;
use App\Enums\UnitUser\ApprovalStatusType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UnitUserExport extends DefaultValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithStyles
{
    protected Collection $records;

    protected array $columns;

    public function __construct(Collection $records, array $columns)
    {
        $this->records = $records;
        $this->columns = $columns;
    }

    protected array $columnLabels = [
        'unit.residence.name' => 'MooBan (โครงการ)',
        'unit_detail' => 'Unit (บ้านเลขที่)',
        'resident_detail' => 'Resident (ลูกบ้าน)',
        'mmb_id' => 'MMB ID',
        'is_owner' => 'Is Owner (เป็นเจ้าของ)',
        'is_main_owner' => 'Is Main Owner (เป็นเจ้าของบ้านหลัก)',
        'is_main_tenant' => 'Is Main Tenant (เป็นผู้เช่าหลัก)',
        'relationship' => 'Relationship (ความสัมพันธ์)',
        'approval_status' => 'Approval',
        'mail_status' => 'Email Status (สถานะอีเมล)',
        'created_at' => 'Created At Date (สร้างเมื่อวันที่)',
        'created_at_time' => 'Created At Time (สร้างเมื่อเวลา)',
    ];

    public function collection()
    {
        if ($this->records instanceof Collection) {
            return $this->records->map(function ($record) {
                // Build the full dataset with labels as keys
                $data = [
                    'MooBan (โครงการ)' => $record?->unit?->residence?->name.' ('.$record?->unit?->residence?->name_th.')',
                    'Unit (บ้านเลขที่)' => 'Street: '.($record?->unit?->street ?? '-')."\n".
                        'Unit Number: '.($record?->unit?->unit_number ?? '-')."\n".
                        'Block: '.($record?->unit?->block ?? '-')."\n".
                        'Floor: '.($record?->unit?->floor ?? '-'),
                    'Resident (ลูกบ้าน)' => 'Name: '.($record?->user?->name ?? '-')."\n".
                        'Email: '.($record?->user?->email ?? '-')."\n".
                        'Phone Number: '.($record?->user?->phone_no ?? '-'),
                    'MMB ID' => $record->mmb_id,
                    'Is Owner (เป็นเจ้าของ)' => $record->is_owner == 1 ? 'yes' : 'no',
                    'Is Main Owner (เป็นเจ้าของบ้านหลัก)' => $record->is_main_owner == 1 ? 'yes' : 'no',
                    'Is Main Tenant (เป็นผู้เช่าหลัก)' => $record->is_main_tenant == 1 ? 'yes' : 'no',
                    'Relationship (ความสัมพันธ์)' => $record->relationship,
                    'Approval' => strtolower(ApprovalStatusType::from($record->approval_status)->name ?? 'UNKNOWN'),
                    'Email Status (สถานะอีเมล)' => $record->mail_status == GeneralStatus::INACTIVE->value ? 'Pending' : 'Verified',
                    'Created At Date (สร้างเมื่อวันที่)' => $record->created_at->format('d-M-Y'),
                    'Created At Time (สร้างเมื่อเวลา)' => $record->created_at->format('H:i:s'),
                ];

                // Convert raw column keys to their display labels
                $selectedLabels = collect($this->columns)->map(function ($key) {
                    return $this->columnLabels[$key] ?? $key;
                });

                return collect($data)->only($selectedLabels);
            });
        }

        return collect();
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

        // Dynamically enable wrapping for specific columns
        $headings = $this->headings();

        // List of labels that need text wrapping
        $wrapLabels = [
            'Unit (บ้านเลขที่)',
            'Resident (ลูกบ้าน)',
        ];

        foreach ($wrapLabels as $label) {
            $index = array_search($label, $headings);
            if ($index !== false) {
                $columnLetter = Coordinate::stringFromColumnIndex($index + 1); // 1-based index
                $sheet->getStyle($columnLetter.'2:'.$columnLetter.$sheet->getHighestRow())
                    ->getAlignment()
                    ->setWrapText(true);
            }
        }
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
