<?php

namespace App\Exports;

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

class SosManagementExport extends DefaultValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithStyles
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
        'unit_number' => 'Unit (บ้านเลขที่)',
        'resident' => 'Resident (ลูกบ้าน)',
        'user_action_request' => 'Type of Alert (ประเภทของการแจ้งเตือน)',
        'status' => 'Status (สถานะ)',
        'remark' => 'Remark (หมายเหตุ)',
        'created_at_date' => 'Created At Date (สร้างเมื่อวันที่)',
        'created_at_time' => 'Created At Time (สร้างเมื่อเวลา)',
    ];

    public function collection()
    {
        if ($this->records instanceof Collection) {
            return $this->records->map(function ($record) {
                // Build the full dataset with labels as keys
                $data = [
                    'unit.residence.name' => $record?->unit?->residence?->name.' ('.$record?->unit?->residence?->name_th.')',
                    'unit_number' => 'Street: '.($record?->unit?->street ?? '-')."\n".
                        'Unit Number: '.($record?->unit?->unit_number ?? '-')."\n".
                        'Block: '.($record?->unit?->block ?? '-')."\n".
                        'Floor: '.($record?->unit?->floor ?? '-'),
                    'resident' => 'Name: '.($record?->createdBy?->name ?? '-')."\n".
                        'Email: '.($record?->createdBy?->email ?? '-')."\n".
                        'Phone Number: '.($record?->createdBy?->phone_no ?? '-'),
                    'user_action_request' => $record?->user_action_request,
                    'status' => $record->status,
                    'remark' => $record->remark,
                    'created_at_date' => $record->created_at->format('d-M-Y'),
                    'created_at_time' => $record->created_at->format('H:i:s'),
                ];

                return collect($data)->only($this->columns);
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
